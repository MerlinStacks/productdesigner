<?php

namespace Custom_Products\Asset_Management;

/**
 * Interface for managing assets.
 *
 * Defines the contract for classes that handle asset-related operations,
 * such as uploading, retrieving, and managing asset data.
 */
interface AssetManagerInterface {

	/**
	 * Uploads an asset.
	 *
	 * @param array $file_data Array of file data, typically from $_FILES.
	 * @return int|\WP_Error The attachment ID on success, or WP_Error on failure.
	 */
	public function upload_asset( array $file_data );

	/**
	 * Retrieves asset data by ID.
	 *
	 * @param int $asset_id The ID of the asset.
	 * @return array|object|null Asset data on success, or null if not found.
	 */
	public function get_asset( int $asset_id );

	/**
	 * Deletes an asset by ID.
	 *
	 * @param int $asset_id The ID of the asset.
	 * @return bool|\WP_Error True on success, or WP_Error on failure.
	 */
	public function delete_asset( int $asset_id );

	/**
	 * Updates asset metadata.
	 *
	 * @param int $asset_id The ID of the asset.
	 * @param array $metadata The metadata to update.
	 * @return bool|\WP_Error True on success, or WP_Error on failure.
	 */
	public function update_asset_metadata( int $asset_id, array $metadata );
}