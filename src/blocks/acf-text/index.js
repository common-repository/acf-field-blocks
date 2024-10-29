/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { acfText as icon } from '../../icons';
import edit from './edit';

const { name } = metadata;

registerBlockType( name, {
	...metadata,
	icon,
	edit,
	save() {
		return <InnerBlocks.Content />;
	}
});
