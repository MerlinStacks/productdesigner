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
     * Upload and process a new asset.
     *
     * @param array $file File data ($_FILES array format).
     * @param string $type Asset type (e.g., 'image', 'font').
     * @param array $metadata Additional metadata.
     * @return array|WP_Error Asset data on success, WP_Error on failure.
     */
    public function uploadAsset(array $file, string $type, array $metadata = []) {
        // Basic validation: Check for upload errors.
        if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
            return new WP_Error( 'upload_error', __( 'Invalid upload parameters.', 'product-personalizer' ) );
        }

        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'upload_error_' . $file['error'], __( 'File upload failed.', 'product-personalizer' ) );
        }

        // Basic validation: Check file size (5MB limit as per security architecture example).
        $max_size = 5 * 1024 * 1024;
        if ( $file['size'] > $max_size ) {
            return new WP_Error( 'file_size_limit', __( 'File size exceeds the maximum allowed (5MB).', 'product-personalizer' ) );
        }

        // Basic validation: Check file type (minimal allowed types for now).
        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
        ];

        $finfo = new \finfo( FILEINFO_MIME_TYPE );
        $mime_type = $finfo->file( $file['tmp_name'] );

        if ( ! in_array( $mime_type, $allowed_types ) ) {
            return new WP_Error( 'invalid_file_type', __( 'File type not allowed.', 'product-personalizer' ) );
        }

        // Secure file storage.
        $upload_dir = wp_upload_dir();
        $asset_dir  = $upload_dir['basedir'] . '/product-personalizer/' . sanitize_key( $type );

        if ( ! file_exists( $asset_dir ) ) {
            wp_mkdir_p( $asset_dir );
            // Add index.php to prevent directory listing.
            file_put_contents( $asset_dir . '/index.php', '<?php // Silence is golden' );
        }

        $filename        = sanitize_file_name( $file['name'] );
        $filename        = wp_unique_filename( $asset_dir, $filename );
        $new_file_path   = $asset_dir . '/' . $filename;
        $new_file_url    = $upload_dir['baseurl'] . '/product-personalizer/' . sanitize_key( $type ) . '/' . $filename;

        if ( move_uploaded_file( $file['tmp_name'], $new_file_path ) ) {
            // Set correct file permissions.
            chmod( $new_file_path, 0644 );

            // Return minimal asset data for now.
            return [
                'id'       => 0, // Placeholder, actual ID would come from database insertion.
                'type'     => $type,
                'url'      => $new_file_url,
                'metadata' => $metadata,
            ];
        } else {
            return new WP_Error( 'file_move_failed', __( 'Failed to move uploaded file.', 'product-personalizer' ) );
        }
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

    /**
     * Delete an asset.
     *
     * @param int $assetId Asset ID.
     * @return bool Success status.
     */
    public function deleteAsset(int $assetId): bool {
        // TODO: Implement asset deletion.
        return false;
    }
}