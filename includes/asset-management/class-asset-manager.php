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
        $table_name_clipart = $wpdb->prefix . 'product_personalizer_clipart';

        // 1. Accept asset metadata and uploaded file object.
        // Metadata is expected in $file_data array, e.g., $file_data['metadata'].
        $metadata = isset( $file_data['metadata'] ) ? $file_data['metadata'] : [];
        $asset_type = isset( $metadata['type'] ) ? sanitize_text_field( $metadata['type'] ) : '';

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

        // Determine file type using finfo.
        $finfo = new \finfo( FILEINFO_MIME_TYPE );
        $mime_type = $finfo->file( $file_data['tmp_name'] );

        $file_url = null;
        $file_path = null;
        $filename = null;
        $asset_name = '';
        $category_id = isset( $metadata['category_id'] ) ? absint( $metadata['category_id'] ) : 0;
        $tags = isset( $metadata['tags'] ) ? (array) $metadata['tags'] : [];


        switch ( $asset_type ) {
            case 'font':
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

                if ( ! in_array( $mime_type, $allowed_font_types ) ) {
                    return new WP_Error( 'invalid_file_type', __( 'File type not allowed for fonts.', 'product-personalizer' ) );
                }

                // Validate required font metadata for custom fonts.
                $font_family = isset( $metadata['font_family'] ) ? sanitize_text_field( $metadata['font_family'] ) : '';
                $font_weight = isset( $metadata['font_weight'] ) ? sanitize_text_field( $metadata['font_weight'] ) : 'normal';
                $font_style = isset( $metadata['font_style'] ) ? sanitize_text_field( $metadata['font_style'] ) : 'normal';
                $source = isset( $metadata['source'] ) ? sanitize_text_field( $metadata['source'] ) : 'custom'; // Default to custom for uploaded files.

                if ( 'custom' === $source && ( empty( $font_family ) || empty( $font_weight ) || empty( $font_style ) ) ) {
                     return new WP_Error( 'missing_font_metadata', __( 'Missing required font metadata for custom font.', 'product-personalizer' ) );
                }

                $asset_name = $font_family; // Use font family as asset name.

                // File Handling (for custom fonts).
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

                // Insert into main assets table.
                $asset_data = [
                    'type'        => 'font',
                    'name'        => $asset_name,
                    'file_name'   => $filename,
                    'file_path'   => $file_path,
                    'url'         => $file_url,
                    'mime_type'   => $mime_type,
                    'file_size'   => $file_data['size'],
                    'metadata'    => json_encode( $metadata ), // Store original metadata.
                    'category_id' => $category_id,
                    'created_at'  => current_time( 'mysql' ),
                    'updated_at'  => current_time( 'mysql' ),
                    'created_by'  => get_current_user_id(),
                    'status'      => 'active',
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

                // Handle tags (if any).
                if ( ! empty( $tags ) ) {
                    // TODO: Implement tag handling (inserting into asset_tags and asset_tag_relationships).
                    // This would involve looking up existing tags by name/slug or creating new ones,
                    // then inserting into the relationship table.
                }

                return $asset_id;

            case 'clipart':
                // Clipart-specific validation: Check file type.
                $allowed_clipart_types = [
                    'image/svg+xml',
                    'image/png',
                    'image/jpeg',
                    'image/gif', // Optionally allow GIFs
                ];

                if ( ! in_array( $mime_type, $allowed_clipart_types ) ) {
                    return new WP_Error( 'invalid_file_type', __( 'File type not allowed for clipart.', 'product-personalizer' ) );
                }

                // Validate required clipart metadata.
                $asset_name = isset( $metadata['name'] ) ? sanitize_text_field( $metadata['name'] ) : '';
                if ( empty( $asset_name ) ) {
                    return new WP_Error( 'missing_clipart_name', __( 'Clipart name is required.', 'product-personalizer' ) );
                }

                // File Handling for clipart.
                $upload_dir = wp_upload_dir();
                $clipart_dir  = $upload_dir['basedir'] . '/product-personalizer/clipart';

                if ( ! file_exists( $clipart_dir ) ) {
                    wp_mkdir_p( $clipart_dir );
                    // Add index.php to prevent directory listing.
                    file_put_contents( $clipart_dir . '/index.php', '<?php // Silence is golden' );
                }

                $filename        = sanitize_file_name( $file_data['name'] );
                $filename        = wp_unique_filename( $clipart_dir, $filename );
                $new_file_path   = $clipart_dir . '/' . $filename;
                $new_file_url    = $upload_dir['baseurl'] . '/product-personalizer/clipart/' . $filename;

                if ( move_uploaded_file( $file_data['tmp_name'], $new_file_path ) ) {
                    // Set correct file permissions.
                    chmod( $new_file_path, 0644 );
                    $file_path = $new_file_path;
                    $file_url = $new_file_url;
                } else {
                    return new WP_Error( 'file_move_failed', __( 'Failed to move uploaded clipart file.', 'product-personalizer' ) );
                }

                // Insert into main assets table.
                $asset_data = [
                    'type'        => 'clipart',
                    'name'        => $asset_name,
                    'file_name'   => $filename,
                    'file_path'   => $file_path,
                    'url'         => $file_url,
                    'mime_type'   => $mime_type,
                    'file_size'   => $file_data['size'],
                    'metadata'    => json_encode( $metadata ), // Store original metadata.
                    'category_id' => $category_id,
                    'created_at'  => current_time( 'mysql' ),
                    'updated_at'  => current_time( 'mysql' ),
                    'created_by'  => get_current_user_id(),
                    'status'      => 'active',
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

                // Insert into clipart-specific asset table.
                $clipart_asset_data = [
                    'asset_id'        => $asset_id,
                    'svg_content'     => ( 'image/svg+xml' === $mime_type ) ? file_get_contents( $file_path ) : null,
                    'width'           => isset( $metadata['width'] ) ? absint( $metadata['width'] ) : null,
                    'height'          => isset( $metadata['height'] ) ? absint( $metadata['height'] ) : null,
                    'is_vector'       => ( 'image/svg+xml' === $mime_type ) ? 1 : 0,
                    'preview_url'     => isset( $metadata['preview_url'] ) ? esc_url_raw( $metadata['preview_url'] ) : null,
                    'color_mode'      => isset( $metadata['color_mode'] ) ? sanitize_text_field( $metadata['color_mode'] ) : 'multi-color',
                    'is_customizable' => isset( $metadata['is_customizable'] ) ? (bool) $metadata['is_customizable'] : 0,
                ];

                $wpdb->insert( $table_name_clipart, $clipart_asset_data );

                if ( ! $wpdb->insert_id ) {
                    // Clean up main asset record and uploaded file if clipart insertion fails.
                    $wpdb->delete( $table_name_assets, [ 'id' => $asset_id ] );
                     if ( $file_path && file_exists( $file_path ) ) {
                        unlink( $file_path );
                    }
                    return new WP_Error( 'db_insert_failed', __( 'Failed to insert clipart asset details into database.', 'product-personalizer' ) );
                }

                 // Handle tags (if any).
                if ( ! empty( $tags ) ) {
                    // TODO: Implement tag handling (inserting into asset_tags and asset_tag_relationships).
                    // This would involve looking up existing tags by name/slug or creating new ones,
                    // then inserting into the relationship table.
                }

                return $asset_id;

            default:
                return new WP_Error( 'invalid_asset_type', __( 'Unsupported asset type provided.', 'product-personalizer' ) );
        }
    }

    /**
     * Adds a new color swatch asset.
     *
     * @param array $color_data Array containing color swatch data (e.g., 'label', 'hex_code').
     * @return int|\WP_Error The new asset ID on success, or WP_Error on failure.
     */
    public function add_color_swatch( array $color_data ) {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_colors = $wpdb->prefix . 'product_personalizer_color_assets';
        $table_name_clipart = $wpdb->prefix . 'product_personalizer_clipart';


        // 1. Accept color swatch metadata.
        $label = isset( $color_data['label'] ) ? sanitize_text_field( $color_data['label'] ) : '';
        $hex_code = isset( $color_data['hex_code'] ) ? sanitize_text_field( $color_data['hex_code'] ) : '';
        $category_id = isset( $color_data['category_id'] ) ? absint( $color_data['category_id'] ) : 0;
        $tags = isset( $color_data['tags'] ) ? (array) $color_data['tags'] : [];


        // 2. Security & Validation.
        if ( empty( $label ) ) {
            return new WP_Error( 'missing_label', __( 'Color swatch label is required.', 'product-personalizer' ) );
        }

        // Validate hex code format (basic validation).
        if ( ! preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $hex_code ) ) {
             return new WP_Error( 'invalid_hex_code', __( 'Invalid hex color code format.', 'product-personalizer' ) );
        }


        // 3. Database Interaction.
        // Insert into main assets table.
        $asset_data = [
            'type'       => 'color',
            'name'       => $label, // Use label as asset name.
            'category_id' => $category_id,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
            'created_by' => get_current_user_id(),
            'status'     => 'active',
        ];

        $wpdb->insert( $table_name_assets, $asset_data );

        $asset_id = $wpdb->insert_id;

        if ( ! $asset_id ) {
            return new WP_Error( 'db_insert_failed', __( 'Failed to insert asset into database.', 'product-personalizer' ) );
        }

        // Insert into color-specific asset table.
        $color_asset_data = [
            'asset_id' => $asset_id,
            'hex_code' => $hex_code,
            'label'    => $label,
            // Add other color-specific fields if needed (rgb_value, cmyk_value, pantone, palette_id).
        ];

        $wpdb->insert( $table_name_colors, $color_asset_data );

        if ( ! $wpdb->insert_id ) {
            // Clean up main asset record if color insertion fails.
            $wpdb->delete( $table_name_assets, [ 'id' => $asset_id ] );
            return new WP_Error( 'db_insert_failed', __( 'Failed to insert color asset details into database.', 'product-personalizer' ) );
        }

        // Handle tags (if any).
        if ( ! empty( $tags ) ) {
            // TODO: Implement tag handling (inserting into asset_tags and asset_tag_relationships).
            // This would involve looking up existing tags by name/slug or creating new ones,
            // then inserting into the relationship table.
        }


        // 4. Return Value.
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
        $table_name_clipart = $wpdb->prefix . 'product_personalizer_clipart';


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

        // 3. Handle type-specific deletion.
        switch ( $asset->type ) {
            case 'font':
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
                break;

            case 'clipart':
                 // If it's a clipart with a file, delete the file.
                if ( ! empty( $asset->file_path ) && file_exists( $asset->file_path ) ) {
                    // Attempt to delete the file.
                    if ( ! unlink( $asset->file_path ) ) {
                        // Log error but continue with database deletion.
                        error_log( sprintf( 'Product Personalizer: Failed to delete clipart file: %s', $asset->file_path ) );
                    }
                }

                // Delete the record from the clipart-specific table.
                $deleted_clipart_row = $wpdb->delete( $table_name_clipart, [ 'asset_id' => $asset_id ] );

                if ( false === $deleted_clipart_row ) {
                    return new WP_Error( 'db_delete_failed', __( 'Failed to delete clipart asset details from database.', 'product-personalizer' ) );
                }
                break;

            case 'color':
                // No file to delete for color swatches.
                // Delete the record from the color-specific table.
                $deleted_color_row = $wpdb->delete( $wpdb->prefix . 'product_personalizer_color_assets', [ 'asset_id' => $asset_id ] );

                if ( false === $deleted_color_row ) {
                    return new WP_Error( 'db_delete_failed', __( 'Failed to delete color asset details from database.', 'product-personalizer' ) );
                }
                break;

            default:
                // No type-specific deletion needed for unknown types, proceed to delete from main table.
                break;
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
     * @param array $metadata The metadata to update. Can include 'file_data' for custom font file replacement.
     * @return bool|\WP_Error True on success, or WP_Error on failure.
     */
    public function update_asset_metadata( int $asset_id, array $metadata ): bool|\WP_Error {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_fonts = $wpdb->prefix . 'product_personalizer_fonts';
        $table_name_clipart = $wpdb->prefix . 'product_personalizer_clipart';


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

        // 2. Validate provided metadata.
        if ( empty( $metadata ) || ! is_array( $metadata ) ) {
            return new WP_Error( 'invalid_metadata', __( 'Invalid metadata provided.', 'product-personalizer' ) );
        }

        // Prepare data for updating the assets table.
        $asset_update_data = [
            'updated_at' => current_time( 'mysql' ),
        ];

        // Prepare data for updating the type-specific table.
        $type_specific_update_data = [];
        $type_specific_table_name = '';

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

        // Handle type-specific metadata updates.
        switch ( $existing_asset->type ) {
            case 'font':
                $type_specific_table_name = $wpdb->prefix . 'product_personalizer_fonts';
                if ( isset( $metadata['font_family'] ) ) {
                    $type_specific_update_data['font_family'] = sanitize_text_field( $metadata['font_family'] );
                }
                if ( isset( $metadata['font_weight'] ) ) {
                    $type_specific_update_data['font_weight'] = sanitize_text_field( $metadata['font_weight'] );
                }
                if ( isset( $metadata['font_style'] ) ) {
                    $type_specific_update_data['font_style'] = sanitize_text_field( $metadata['font_style'] );
                }
                if ( isset( $metadata['source'] ) ) {
                    $type_specific_update_data['source'] = sanitize_text_field( $metadata['source'] );
                }
                if ( isset( $metadata['css_url'] ) ) {
                    $type_specific_update_data['css_url'] = esc_url_raw( $metadata['css_url'] );
                }
                if ( isset( $metadata['variants'] ) ) {
                    $type_specific_update_data['variants'] = json_encode( $metadata['variants'] );
                }
                 if ( isset( $metadata['license_info'] ) ) {
                    $type_specific_update_data['license_info'] = sanitize_text_field( $metadata['license_info'] );
                 }
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
                 break;
             case 'color':
                 $type_specific_table_name = $wpdb->prefix . 'product_personalizer_color_assets';
                 if ( isset( $metadata['label'] ) ) {
                     $label = sanitize_text_field( $metadata['label'] );
                     if ( empty( $label ) ) {
                         return new WP_Error( 'missing_label', __( 'Color swatch label is required.', 'product-personalizer' ) );
                     }
                     $type_specific_update_data['label'] = $label;
                     // Also update the main asset name if label is provided.
                     $asset_update_data['name'] = $label;
                 }
                 if ( isset( $metadata['hex_code'] ) ) {
                     $hex_code = sanitize_text_field( $metadata['hex_code'] );
                     // Validate hex code format (basic validation).
                     if ( ! preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $hex_code ) ) {
                          return new WP_Error( 'invalid_hex_code', __( 'Invalid hex color code format.', 'product-personalizer' ) );
                     }
                     $type_specific_update_data['hex_code'] = $hex_code;
                 }
                 if ( isset( $metadata['rgb_value'] ) ) {
                     $type_specific_update_data['rgb_value'] = sanitize_text_field( $metadata['rgb_value'] );
                 }
                 if ( isset( $metadata['cmyk_value'] ) ) {
                     $type_specific_update_data['cmyk_value'] = sanitize_text_field( $metadata['cmyk_value'] );
                 }
                 if ( isset( $metadata['pantone'] ) ) {
                     $type_specific_update_data['pantone'] = sanitize_text_field( $metadata['pantone'] );
                 }
                 if ( isset( $metadata['palette_id'] ) ) {
                     $type_specific_update_data['palette_id'] = absint( $metadata['palette_id'] );
                 }
                 break;
             case 'clipart':
                 $type_specific_table_name = $wpdb->prefix . 'product_personalizer_clipart';
                 if ( isset( $metadata['svg_content'] ) ) {
                     $type_specific_update_data['svg_content'] = $metadata['svg_content']; // Assuming SVG content is already sanitized if provided directly.
                 }
                 if ( isset( $metadata['width'] ) ) {
                     $type_specific_update_data['width'] = absint( $metadata['width'] );
                 }
                 if ( isset( $metadata['height'] ) ) {
                     $type_specific_update_data['height'] = absint( $metadata['height'] );
                 }
                 if ( isset( $metadata['is_vector'] ) ) {
                     $type_specific_update_data['is_vector'] = (bool) $metadata['is_vector'];
                 }
                 if ( isset( $metadata['preview_url'] ) ) {
                     $type_specific_update_data['preview_url'] = esc_url_raw( $metadata['preview_url'] );
                 }
                 if ( isset( $metadata['color_mode'] ) ) {
                     $type_specific_update_data['color_mode'] = sanitize_text_field( $metadata['color_mode'] );
                 }
                 if ( isset( $metadata['is_customizable'] ) ) {
                     $type_specific_update_data['is_customizable'] = (bool) $metadata['is_customizable'];
                 }
                 // Handle file upload for clipart if provided.
                 if ( isset( $metadata['file_data'] ) && is_array( $metadata['file_data'] ) ) {
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
                     $allowed_clipart_types = [
                         'image/svg+xml',
                         'image/png',
                         'image/jpeg',
                         'image/gif', // Optionally allow GIFs
                     ];

                     $finfo = new \finfo( FILEINFO_MIME_TYPE );
                     $mime_type = $finfo->file( $file_data['tmp_name'] );

                     if ( ! in_array( $mime_type, $allowed_clipart_types ) ) {
                         return new WP_Error( 'invalid_file_type', __( 'New file type not allowed for clipart.', 'product-personalizer' ) );
                     }

                     // Delete the old clipart file if it exists.
                     if ( $existing_asset->file_path && file_exists( $existing_asset->file_path ) ) {
                         unlink( $existing_asset->file_path );
                     }

                     // Securely move the new validated uploaded file.
                     $upload_dir = wp_upload_dir();
                     $clipart_dir  = $upload_dir['basedir'] . '/product-personalizer/clipart';

                     if ( ! file_exists( $clipart_dir ) ) {
                         wp_mkdir_p( $clipart_dir );
                         // Add index.php to prevent directory listing.
                         file_put_contents( $clipart_dir . '/index.php', '<?php // Silence is golden' );
                     }

                     $filename        = sanitize_file_name( $file_data['name'] );
                     $filename        = wp_unique_filename( $clipart_dir, $filename );
                     $new_file_path   = $clipart_dir . '/' . $filename;
                     $new_file_url    = $upload_dir['baseurl'] . '/product-personalizer/clipart/' . $filename;

                     if ( move_uploaded_file( $file_data['tmp_name'], $new_file_path ) ) {
                         // Set correct file permissions.
                         chmod( $new_file_path, 0644 );
                         $asset_update_data['file_name'] = $filename;
                         $asset_update_data['file_path'] = $new_file_path;
                         $asset_update_data['url']       = $new_file_url;
                         $asset_update_data['mime_type'] = $mime_type;
                         $asset_update_data['file_size'] = $file_data['size'];

                         // If the new file is an SVG, update svg_content in the clipart table.
                         if ( 'image/svg+xml' === $mime_type ) {
                             $type_specific_update_data['svg_content'] = file_get_contents( $new_file_path );
                         } else {
                             // If replacing an SVG with a non-SVG, clear svg_content.
                             $type_specific_update_data['svg_content'] = null;
                         }
                         // Update is_vector based on the new file type.
                         $type_specific_update_data['is_vector'] = ( 'image/svg+xml' === $mime_type ) ? 1 : 0;

                     } else {
                         return new WP_Error( 'file_move_failed', __( 'Failed to move uploaded clipart file for update.', 'product-personalizer' ) );
                     }
                 }
                 break;
             default:
                 // Handle updates for other asset types or return an error if unsupported.
                 // For now, we'll just proceed with common asset updates for unknown types.
                 break;
         }

         // Update the main assets table if there's data to update.
         if ( ! empty( $asset_update_data ) ) {
             $updated_asset_row = $wpdb->update(
                 $table_name_assets,
                 $asset_update_data,
                 [ 'id' => $asset_id ]
             );

             if ( false === $updated_asset_row ) {
                 return new WP_Error( 'db_update_failed', __( 'Failed to update asset in database.', 'product-personalizer' ) );
             }
         }

         // Update the type-specific table if there's data to update and a table name is set.
         if ( ! empty( $type_specific_update_data ) && ! empty( $type_specific_table_name ) ) {
             $updated_type_specific_row = $wpdb->update(
                 $type_specific_table_name,
                 $type_specific_update_data,
                 [ 'asset_id' => $asset_id ]
             );

             if ( false === $updated_type_specific_row ) {
                 return new WP_Error( 'db_update_failed', __( 'Failed to update type-specific asset details in database.', 'product-personalizer' ) );
             }
         }

         // TODO: Implement tag handling for updates.
         // This would involve comparing existing tags with new tags,
         // adding new tags, removing old tags, and updating the relationship table.

         // Return success.
         return true;
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

        $query = "SELECT a.*, f.* FROM {$table_name_assets} a JOIN {$table_name_fonts} f ON a.id = f.asset_id WHERE a.type = 'font'";
        $params = [];

        if ( isset( $args['id'] ) ) {
            $query .= " AND a.id = %d";
            $params[] = $args['id'];
        }
        if ( isset( $args['font_family'] ) ) {
            $query .= " AND f.font_family = %s";
            $params[] = $args['font_family'];
        }
        if ( isset( $args['source'] ) ) {
            $query .= " AND f.source = %s";
            $params[] = $args['source'];
        }
        // Add other filtering conditions as needed.

        $results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        return $results ? $results : [];
    }

    /**
     * Retrieves color swatch asset data based on provided criteria.
     *
     * @param array $args Optional arguments for filtering (e.g., 'id', 'label', 'color_code').
     * @return array An array of color swatch objects on success, or an empty array if none found.
     */
    public function get_color_swatches( array $args = [] ): array {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_colors = $wpdb->prefix . 'product_personalizer_color_assets';

        $query = "SELECT a.*, c.* FROM {$table_name_assets} a JOIN {$table_name_colors} c ON a.id = c.asset_id WHERE a.type = 'color'";
        $params = [];

        if ( isset( $args['id'] ) ) {
            $query .= " AND a.id = %d";
            $params[] = $args['id'];
        }
        if ( isset( $args['label'] ) ) {
            $query .= " AND c.label = %s";
            $params[] = $args['label'];
        }
        if ( isset( $args['hex_code'] ) ) {
            $query .= " AND c.hex_code = %s";
            $params[] = $args['hex_code'];
        }
        // Add other filtering conditions as needed.

        $results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        return $results ? $results : [];
    }

    /**
     * Retrieves clipart asset data based on provided criteria.
     *
     * @param array $args Optional arguments for filtering (e.g., 'id', 'name', 'category_id', 'is_vector').
     * @return array An array of clipart objects on success, or an empty array if none found.
     */
    public function get_clipart( array $args = [] ): array {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';
        $table_name_clipart = $wpdb->prefix . 'product_personalizer_clipart';

        $query = "SELECT a.*, cl.* FROM {$table_name_assets} a JOIN {$table_name_clipart} cl ON a.id = cl.asset_id WHERE a.type = 'clipart'";
        $params = [];

        if ( isset( $args['id'] ) ) {
            $query .= " AND a.id = %d";
            $params[] = $args['id'];
        }
        if ( isset( $args['name'] ) ) {
            $query .= " AND a.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like( $args['name'] ) . '%';
        }
        if ( isset( $args['category_id'] ) ) {
            $query .= " AND a.category_id = %d";
            $params[] = $args['category_id'];
        }
        if ( isset( $args['is_vector'] ) ) {
            $query .= " AND cl.is_vector = %d";
            $params[] = (bool) $args['is_vector'];
        }
        // Add other filtering conditions as needed.

        $results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        return $results ? $results : [];
    }

    /**
     * Retrieves the URL for a given asset ID.
     *
     * @param int $assetId The ID of the asset.
     * @return string|null The asset URL on success, or null if not found.
     */
    public function getAssetUrl(int $assetId): ?string {
        global $wpdb;
        $table_name_assets = $wpdb->prefix . 'product_personalizer_assets';

        $url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT url FROM {$table_name_assets} WHERE id = %d",
                $assetId
            )
        );

        return $url ? $url : null;
    }

    /**
     * Optimizes an asset.
     *
     * @param int $assetId The ID of the asset.
     * @param array $options Optional optimization options.
     * @return bool True on success, false on failure.
     */
    public function optimizeAsset(int $assetId, array $options = []): bool {
        // TODO: Implement asset optimization logic (e.g., image compression, SVG optimization).
        return false; // Placeholder.
    }
}