<?php
/**
 * Asset Manager Class
 *
 * Handles asset uploads, management, and optimization.
 *
 * @package ProductPersonalizer
 * @subpackage AssetManagement
 * @since 1.0.0
 */

namespace ProductPersonalizer\AssetManagement;

use ProductPersonalizer\AssetManagement\AssetManagerInterface;
use WP_Error;

class Asset_Manager implements AssetManagerInterface {

    /**
     * Uploads an asset.
     *
     * @param array $file_data Array of file data, typically from $_FILES.
     * @return int|\WP_Error The attachment ID on success, or WP_Error on failure.
     */
    public function upload_asset( array $file_data ) {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_fonts = $wpdb->prefix . 'product_personalizer_fonts';

        // 1. Accept font metadata and uploaded file object.
        // Metadata is expected in $file_data array, e.g., $file_data['metadata'].
        $metadata = isset( $file_data['metadata'] ) ? $file_data['metadata'] : [];
        $font_family = isset( $metadata['font_family'] ) ? sanitize_text_field( $metadata['font_family'] ) : '';
        $font_weight = isset( $metadata['font_weight'] ) ? sanitize_text_field( $metadata['font_weight'] ) : 'normal';
        $font_style = isset( $metadata['font_style'] ) ? sanitize_text_field( $metadata['font_style'] ) : 'normal';
        $source = isset( $metadata['source'] ) ? sanitize_text_field( $metadata['source'] ) : 'custom'; // Default to custom for uploaded files.

        // 2. Security & Validation.
        // Basic validation: Check for upload errors.
        if ( ! isset( $file_data['error'] ) || is_array( $file_data['error'] ) ) {
            return new WP_Error( 'upload_error', __( 'Invalid upload parameters.', 'product-personalizer' ) );
        }

        if ( $file_data['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'upload_error_' . $file_data['error'], __( 'File upload failed.', 'product-personalizer' ) );
        }

        // Basic validation: Check file size (5MB limit).
        $max_size = 5 * 1024 * 1024;
        if ( $file_data['size'] > $max_size ) {
            return new WP_Error( 'file_size_limit', __( 'File size exceeds the maximum allowed (5MB).', 'product-personalizer' ) );
        }

        // Font-specific validation: Check file type.
        $allowed_font_types = [
            'font/ttf',
            'font/otf',
            'font/woff',
            'font/woff2',
            'application/font-sfnt', // Common for TTF/OTF
            'application/font-woff',
            'application/font-woff2',
            'application/octet-stream', // Sometimes used for fonts
        ];

        $finfo = new \finfo( FILEINFO_MIME_TYPE );
        $mime_type = $finfo->file( $file_data['tmp_name'] );

        if ( ! in_array( $mime_type, $allowed_font_types ) ) {
            return new WP_Error( 'invalid_file_type', __( 'File type not allowed for fonts.', 'product-personalizer' ) );
        }

        // Validate required font metadata for custom fonts.
        if ( 'custom' === $source && ( empty( $font_family ) || empty( $font_weight ) || empty( $font_style ) ) ) {
             return new WP_Error( 'missing_font_metadata', __( 'Missing required font metadata for custom font.', 'product-personalizer' ) );
        }


        // 3. File Handling (for custom fonts).
        $file_url = null;
        $file_path = null;
        $filename = null;

        if ( 'custom' === $source ) {
            $upload_dir = wp_upload_dir();
            $font_dir  = $upload_dir['basedir'] . '/product-personalizer/fonts';

            if ( ! file_exists( $font_dir ) ) {
                wp_mkdir_p( $font_dir );
                // Add index.php to prevent directory listing.
                file_put_contents( $font_dir . '/index.php', '<?php // Silence is golden' );
            }

            $filename        = sanitize_file_name( $file_data['name'] );
            $filename        = wp_unique_filename( $font_dir, $filename );
            $new_file_path   = $font_dir . '/' . $filename;
            $new_file_url    = $upload_dir['baseurl'] . '/product-personalizer/fonts/' . $filename;

            if ( move_uploaded_file( $file_data['tmp_name'], $new_file_path ) ) {
                // Set correct file permissions.
                chmod( $new_file_path, 0644 );
                $file_path = $new_file_path;
                $file_url = $new_file_url;
            } else {
                return new WP_Error( 'file_move_failed', __( 'Failed to move uploaded font file.', 'product-personalizer' ) );
            }
        }


        // 4. Database Interaction.
        // Insert into main assets table.
        $asset_data = [
            'type'      => 'font',
            'name'      => $font_family, // Use font family as asset name.
            'file_name' => $filename,
            'file_path' => $file_path,
            'url'       => $file_url,
            'mime_type' => $mime_type,
            'file_size' => $file_data['size'],
            'metadata'  => json_encode( $metadata ), // Store original metadata.
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
            'status'    => 'active',
        ];

        $wpdb->insert( $table_name_assets, $asset_data );

        $asset_id = $wpdb->insert_id;

        if ( ! $asset_id ) {
            // Clean up uploaded file if database insertion fails.
            if ( $file_path && file_exists( $file_path ) ) {
                unlink( $file_path );
            }
            return new WP_Error( 'db_insert_failed', __( 'Failed to insert asset into database.', 'product-personalizer' ) );
        }

        // Insert into font-specific asset table.
        $font_asset_data = [
            'asset_id'    => $asset_id,
            'font_family' => $font_family,
            'font_weight' => $font_weight,
            'font_style'  => $font_style,
            'source'      => $source,
            'css_url'     => isset( $metadata['css_url'] ) ? esc_url_raw( $metadata['css_url'] ) : null, // For Google Fonts etc.
            'variants'    => isset( $metadata['variants'] ) ? json_encode( $metadata['variants'] ) : null,
            'license_info' => isset( $metadata['license_info'] ) ? sanitize_text_field( $metadata['license_info'] ) : null,
        ];

        $wpdb->insert( $table_name_fonts, $font_asset_data );

        if ( ! $wpdb->insert_id ) {
            // Clean up main asset record and uploaded file if font insertion fails.
            $wpdb->delete( $table_name_assets, [ 'id' => $asset_id ] );
             if ( $file_path && file_exists( $file_path ) ) {
                unlink( $file_path );
            }
            return new WP_Error( 'db_insert_failed', __( 'Failed to insert font asset details into database.', 'product-personalizer' ) );
        }

        // 5. Return Value.
        return $asset_id;
    }

    /**
     * Retrieves asset data by ID.
     *
     * @param int $asset_id The ID of the asset.
     * @return array|object|null Asset data on success, or null if not found.
     */
    public function get_asset( int $asset_id ) {
        // TODO: Implement this method.
        return null;
    }

    /**
     * Deletes an asset by ID.
     *
     * @param int $asset_id The ID of the asset.
     * @return bool|\WP_Error True on success, or WP_Error on failure.
     */
    public function delete_asset( int $asset_id ): bool|\WP_Error {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_fonts = $wpdb->prefix . 'product_personalizer_fonts';

        // 1. Validate asset ID.
        if ( empty( $asset_id ) || ! is_int( $asset_id ) ) {
            return new WP_Error( 'invalid_asset_id', __( 'Invalid asset ID provided.', 'product-personalizer' ) );
        }

        // 2. Retrieve asset data.
        $asset = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name_assets} WHERE id = %d",
                $asset_id
            )
        );

        if ( ! $asset ) {
            return new WP_Error( 'asset_not_found', __( 'Asset not found.', 'product-personalizer' ) );
        }

        // 3. Handle font-specific deletion (if applicable).
        if ( 'font' === $asset->type ) {
            // Retrieve font-specific data.
            $font_asset = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name_fonts} WHERE asset_id = %d",
                    $asset_id
                )
            );

            if ( $font_asset ) {
                // If it's a custom font with a file, delete the file.
                if ( 'custom' === $font_asset->source && ! empty( $asset->file_path ) && file_exists( $asset->file_path ) ) {
                    // Attempt to delete the file.
                    if ( ! unlink( $asset->file_path ) ) {
                        // Log error but continue with database deletion.
                        error_log( sprintf( 'Product Personalizer: Failed to delete font file: %s', $asset->file_path ) );
                    }
                }

                // Delete the record from the font-specific table.
                $deleted_font_row = $wpdb->delete( $table_name_fonts, [ 'asset_id' => $asset_id ] );

                if ( false === $deleted_font_row ) {
                    return new WP_Error( 'db_delete_failed', __( 'Failed to delete font asset details from database.', 'product-personalizer' ) );
                }
            }
        }

        // 4. Delete the record from the main assets table.
        $deleted_asset_row = $wpdb->delete( $table_name_assets, [ 'id' => $asset_id ] );

        if ( false === $deleted_asset_row ) {
            return new WP_Error( 'db_delete_failed', __( 'Failed to delete asset from database.', 'product-personalizer' ) );
        }

        // 5. Return success.
        return true;
    }

    /**
     * Updates asset metadata.
     *
     * @param int $asset_id The ID of the asset.
     * @param array $metadata The metadata to update.
     * @return bool|\WP_Error True on success, or WP_Error on failure.
     */
    /**
     * Updates asset metadata.
     *
     * @param int $asset_id The ID of the asset.
     * @param array $metadata The metadata to update. Can include 'file_data' for custom font file replacement.
     * @return bool|\WP_Error True on success, or WP_Error on failure.
     */
    public function update_asset_metadata( int $asset_id, array $metadata ): bool|\WP_Error {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_fonts = $wpdb->prefix . 'product_personalizer_fonts';

        // 1. Validate asset ID.
        if ( empty( $asset_id ) || ! is_int( $asset_id ) ) {
            return new WP_Error( 'invalid_asset_id', __( 'Invalid asset ID provided.', 'product-personalizer' ) );
        }

        // Retrieve existing asset data.
        $existing_asset = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name_assets} WHERE id = %d",
                $asset_id
            )
        );

        if ( ! $existing_asset ) {
            return new WP_Error( 'asset_not_found', __( 'Asset not found.', 'product-personalizer' ) );
        }

        // Ensure the asset is a font.
        if ( 'font' !== $existing_asset->type ) {
            return new WP_Error( 'invalid_asset_type', __( 'Asset is not a font.', 'product-personalizer' ) );
        }

        // 2. Validate provided metadata.
        if ( empty( $metadata ) || ! is_array( $metadata ) ) {
            return new WP_Error( 'invalid_metadata', __( 'Invalid metadata provided.', 'product-personalizer' ) );
        }

        // Prepare data for updating the assets table.
        $asset_update_data = [
            'updated_at' => current_time( 'mysql' ),
        ];

        // Prepare data for updating the font-specific table.
        $font_update_data = [];

        // Handle common asset metadata updates.
        if ( isset( $metadata['name'] ) ) {
            $asset_update_data['name'] = sanitize_text_field( $metadata['name'] );
        }
        if ( isset( $metadata['category_id'] ) ) {
             $asset_update_data['category_id'] = absint( $metadata['category_id'] );
        }
        if ( isset( $metadata['status'] ) ) {
             $asset_update_data['status'] = sanitize_text_field( $metadata['status'] );
        }
        // Add other common fields as needed.

        // Handle font-specific metadata updates.
        if ( isset( $metadata['font_family'] ) ) {
            $font_update_data['font_family'] = sanitize_text_field( $metadata['font_family'] );
        }
        if ( isset( $metadata['font_weight'] ) ) {
            $font_update_data['font_weight'] = sanitize_text_field( $metadata['font_weight'] );
        }
        if ( isset( $metadata['font_style'] ) ) {
            $font_update_data['font_style'] = sanitize_text_field( $metadata['font_style'] );
        }
        if ( isset( $metadata['source'] ) ) {
            $font_update_data['source'] = sanitize_text_field( $metadata['source'] );
        }
        if ( isset( $metadata['css_url'] ) ) {
            $font_update_data['css_url'] = esc_url_raw( $metadata['css_url'] );
        }
        if ( isset( $metadata['variants'] ) ) {
            $font_update_data['variants'] = json_encode( $metadata['variants'] );
        }
         if ( isset( $metadata['license_info'] ) ) {
            $font_update_data['license_info'] = sanitize_text_field( $metadata['license_info'] );
        }
        // Add other font-specific fields as needed.

        // Handle file upload for custom fonts if provided.
        if ( 'custom' === $existing_asset->source && isset( $metadata['file_data'] ) && is_array( $metadata['file_data'] ) ) {
            $file_data = $metadata['file_data'];

            // Perform security checks on the new file.
            if ( ! isset( $file_data['error'] ) || is_array( $file_data['error'] ) ) {
                return new WP_Error( 'upload_error', __( 'Invalid upload parameters for file update.', 'product-personalizer' ) );
            }

            if ( $file_data['error'] !== UPLOAD_ERR_OK ) {
                return new WP_Error( 'upload_error_' . $file_data['error'], __( 'File upload failed for update.', 'product-personalizer' ) );
            }

            // Check file size (5MB limit).
            $max_size = 5 * 1024 * 1024;
            if ( $file_data['size'] > $max_size ) {
                return new WP_Error( 'file_size_limit', __( 'New file size exceeds the maximum allowed (5MB).', 'product-personalizer' ) );
            }

            // Check file type.
            $allowed_font_types = [
                'font/ttf',
                'font/otf',
                'font/woff',
                'font/woff2',
                'application/font-sfnt', // Common for TTF/OTF
                'application/font-woff',
                'application/font-woff2',
                'application/octet-stream', // Sometimes used for fonts
            ];

            $finfo = new \finfo( FILEINFO_MIME_TYPE );
            $mime_type = $finfo->file( $file_data['tmp_name'] );

            if ( ! in_array( $mime_type, $allowed_font_types ) ) {
                return new WP_Error( 'invalid_file_type', __( 'New file type not allowed for fonts.', 'product-personalizer' ) );
            }

            // Delete the old font file if it exists.
            if ( $existing_asset->file_path && file_exists( $existing_asset->file_path ) ) {
                unlink( $existing_asset->file_path );
            }

            // Securely move the new validated uploaded file.
            $upload_dir = wp_upload_dir();
            $font_dir  = $upload_dir['basedir'] . '/product-personalizer/fonts';

            if ( ! file_exists( $font_dir ) ) {
                wp_mkdir_p( $font_dir );
                // Add index.php to prevent directory listing.
                file_put_contents( $font_dir . '/index.php', '<?php // Silence is golden' );
            }

            $filename        = sanitize_file_name( $file_data['name'] );
            $filename        = wp_unique_filename( $font_dir, $filename );
            $new_file_path   = $font_dir . '/' . $filename;
            $new_file_url    = $upload_dir['baseurl'] . '/product-personalizer/fonts/' . $filename;

            if ( move_uploaded_file( $file_data['tmp_name'], $new_file_path ) ) {
                // Set correct file permissions.
                chmod( $new_file_path, 0644 );
                $asset_update_data['file_name'] = $filename;
                $asset_update_data['file_path'] = $new_file_path;
                $asset_update_data['url']       = $new_file_url;
                $asset_update_data['mime_type'] = $mime_type;
                $asset_update_data['file_size'] = $file_data['size'];
            } else {
                return new WP_Error( 'file_move_failed', __( 'Failed to move uploaded font file for update.', 'product-personalizer' ) );
            }
        }

        // 4. Database Interaction.
        // Update main assets table.
        if ( ! empty( $asset_update_data ) ) {
            $asset_updated = $wpdb->update(
                $table_name_assets,
                $asset_update_data,
                [ 'id' => $asset_id ]
            );

            if ( false === $asset_updated ) {
                 // If file was uploaded, clean it up on database update failure.
                if ( isset( $asset_update_data['file_path'] ) && file_exists( $asset_update_data['file_path'] ) ) {
                    unlink( $asset_update_data['file_path'] );
                }
                return new WP_Error( 'db_update_failed', __( 'Failed to update asset in database.', 'product-personalizer' ) );
            }
        }


        // Update font-specific asset table.
        if ( ! empty( $font_update_data ) ) {
             $font_updated = $wpdb->update(
                $table_name_fonts,
                $font_update_data,
                [ 'asset_id' => $asset_id ]
            );

            if ( false === $font_updated ) {
                 // If file was uploaded, clean it up on database update failure.
                if ( isset( $asset_update_data['file_path'] ) && file_exists( $asset_update_data['file_path'] ) ) {
                    unlink( $asset_update_data['file_path'] );
                }
                return new WP_Error( 'db_update_failed', __( 'Failed to update font asset details in database.', 'product-personalizer' ) );
            }
        }

        // 5. Return Value.
        // Check if either table was updated or if a file was handled.
        if ( ( ! empty( $asset_update_data ) && false !== $asset_updated ) || ( ! empty( $font_update_data ) && false !== $font_updated ) || ( isset( $metadata['file_data'] ) && is_array( $metadata['file_data'] ) && false !== $asset_updated ) ) {
             return true;
        } else {
             // No data to update or update resulted in no changes.
             return new WP_Error( 'no_changes', __( 'No changes were made to the asset.', 'product-personalizer' ) );
        }
    }

    /**
     * Retrieves font asset data based on provided criteria.
     *
     * @param array $args Optional arguments for filtering (e.g., 'id', 'font_family', 'source').
     * @return array An array of font objects on success, or an empty array if none found.
     */
    public function get_fonts( array $args = [] ): array {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_fonts = $wpdb->prefix . 'product_personalizer_fonts';

        $sql = "SELECT a.*, f.*
                FROM {$table_name_assets} a
                JOIN {$table_name_fonts} f ON a.id = f.asset_id
                WHERE a.type = 'font'";

        $where_clauses = [];
        $params = [];

        if ( ! empty( $args['id'] ) ) {
            $where_clauses[] = "a.id = %d";
            $params[] = $args['id'];
        }

        if ( ! empty( $args['font_family'] ) ) {
            $where_clauses[] = "f.font_family = %s";
            $params[] = $args['font_family'];
        }

        if ( ! empty( $args['source'] ) ) {
            $where_clauses[] = "f.source = %s";
            $params[] = $args['source'];
        }

        // Add more filtering options as needed based on schema

        if ( ! empty( $where_clauses ) ) {
            $sql .= " AND " . implode( " AND ", $where_clauses );
        }

        $sql .= " AND a.status = 'active'"; // Only retrieve active fonts.

        $prepared_sql = $wpdb->prepare( $sql, $params );

        $results = $wpdb->get_results( $prepared_sql );

        if ( ! $results ) {
            return [];
        }

        // Format results if necessary, though $wpdb->get_results often returns objects suitable for direct use.
        // For now, return as is. Further formatting can be added if a specific structure is required.
        return $results;
    }

    /**
     * Get URL for an asset.
     *
     * @param int $assetId Asset ID.
     * @return string|null Asset URL or null if not found.
     */
    public function getAssetUrl(int $assetId): ?string {
        // TODO: Implement fetching asset URL from database.
        return null;
    }

    /**
     * Optimize an asset.
     *
     * @param int $assetId Asset ID.
     * @param array $options Optimization options.
     * @return bool Success status.
     */
    public function optimizeAsset(int $assetId, array $options = []): bool {
        // TODO: Implement asset optimization.
        return false;
    }
}