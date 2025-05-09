<?php
/**
 * Asset Manager Interface
 *
 * Defines the interface for the Asset Management component.
 *
 * @package ProductPersonalizer
 * @subpackage AssetManagement
 * @since 1.0.0
 */

namespace ProductPersonalizer\AssetManagement;

use WP_Error;

interface AssetManagerInterface {
    /**
     * Upload and process a new asset
     * @param array $file File data ($_FILES array format)
     * @param string $type Asset type
     * @param array $metadata Additional metadata
     * @return array|WP_Error Asset data on success, WP_Error on failure
     */
    public function uploadAsset(array $file, string $type, array $metadata = []);

    /**
     * Get URL for an asset
     * @param int $assetId Asset ID
     * @return string|null Asset URL or null if not found
     */
    public function getAssetUrl(int $assetId): ?string;

    /**
     * Optimize an asset
     * @param int $assetId Asset ID
     * @param array $options Optimization options
     * @return bool Success status
     */
    public function optimizeAsset(int $assetId, array $options = []): bool;

    /**
     * Delete an asset
     * @param int $assetId Asset ID
     * @return bool Success status
     */
    public function deleteAsset(int $assetId): bool;
}