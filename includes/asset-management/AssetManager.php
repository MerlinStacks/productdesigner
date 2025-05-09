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

class AssetManager implements AssetManagerInterface {

    /**
     * Upload and process a new asset.
     *
     * @param array $file File data ($_FILES array format).
     * @param string $type Asset type (e.g., 'image', 'font').
     * @param array $metadata Additional metadata.
     * @return array|WP_Error Asset data on success, WP_Error on failure.
     */
    public function uploadAsset(array $file, string $type, array $metadata = []) {
        // Check user capabilities.
        if ( ! current_user_can( 'upload_files' ) ) {
            return new WP_Error( 'upload_permission_denied', __( 'You do not have permission to upload files.', 'product-personalizer' ) );
        }

        // Explicitly check file type before handling upload.
        $filetype = wp_check_filetype( $file['name'], $allowed_types );
        if ( ! $filetype['ext'] || ! $filetype['type'] ) {
            return new WP_Error( 'upload_failed', __( 'Invalid file type.', 'product-personalizer' ) );
        }


        // Define upload overrides.
        $upload_overrides = [
            'test_form' => false, // Important for non-traditional forms.
            'unique_filename_callback' => function( $dir, $name, $ext ) use ( $type ) {
                $filename = sanitize_file_name( $name );
                // Ensure unique filename within the asset type directory.
                return wp_unique_filename( $dir . '/product-personalizer/' . sanitize_key( $type ), $filename );
            },
        ];

        // Define allowed file types based on the asset type.
        $allowed_types = $this->getAllowedMimeTypes( $type );
        if ( is_wp_error( $allowed_types ) ) {
            return $allowed_types;
        }
        $upload_overrides['mimes'] = $allowed_types;

        // Handle the file upload using WordPress function.
        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        // Check for upload errors.
        if ( isset( $uploaded_file['error'] ) ) {
            return new WP_Error( 'upload_failed', $uploaded_file['error'] );
        }

        // On success, return the uploaded file data.
        // Add asset type and metadata to the returned array.
        $uploaded_file['type'] = $type;
        $uploaded_file['metadata'] = $metadata;

        return $uploaded_file;
    }

    /**
     * Get allowed mime types based on asset type.
     *
     * @param string $type Asset type.
     * @return array|WP_Error Allowed mime types or WP_Error if type is invalid.
     */
    private function getAllowedMimeTypes(string $type): array|WP_Error {
        $allowed_types = [];
        switch ( $type ) {
            case 'image':
                $allowed_types = [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                    'gif'          => 'image/gif',
                    'svg'          => 'image/svg+xml',
                ];
                break;
            case 'font':
                $allowed_types = [
                    'woff'  => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf'   => 'font/ttf',
                    'otf'   => 'font/otf',
                ];
                break;
            // Add more asset types and their allowed mime types here.
            default:
                return new WP_Error( 'invalid_asset_type', __( 'Invalid asset type specified.', 'product-personalizer' ) );
        }

        /**
         * Filters the allowed mime types for a specific asset type.
         *
         * @since 1.0.0
         * @param array $allowed_types Allowed mime types.
         * @param string $type Asset type.
         */
        return apply_filters( "product_personalizer_allowed_asset_mime_types_{$type}", $allowed_types, $type );
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