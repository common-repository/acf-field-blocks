import {
	useSelect,
	select
} from '@wordpress/data';
import {
	SelectControl,
	BaseControl,
	Spinner
} from '@wordpress/components';
import {
	useEffect,
	useState
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useFieldsLoader
} from '../functions'

const FieldSourceControl = ({
	attributes,
	setAttributes,
	hasNoMarginBottom = false,
	clientId,
	context,
	help = null
}) => {

	const [ ancestorOptions, setAncestorOptions ]         = useState([]);
	const [ isTermLoopDecendant, setIsTermLoopDecendant ] = useState(false);
	const [ isTermTemplate, setIsTermTemplate ]           = useState(false);
	const [ isUserLoopDecendant, setIsUserLoopDecendant ] = useState(false);
	const [ isUserTemplate, setIsUserTemplate ]           = useState(false);
	const isSiteEditor = typeof window.pagenow !== 'undefined' && 'site-editor' === window.pagenow;

	const {
		getField,
		isLoadingFields
	} = useFieldsLoader( attributes.fieldSource, context );

	useEffect( () => {
		if ( isSiteEditor ) {
			const siteTemplate = select('core/edit-site').getEditedPostId();
			let templateSlug = '';
			[ , templateSlug ] = siteTemplate.split('//');
			if ( 'category' === templateSlug || 'tag' === templateSlug || templateSlug.startsWith('category-') || templateSlug.startsWith('tag-') || templateSlug.startsWith('taxonomy-') ) {
				setIsTermTemplate(true);
			} else if ( 'author' === templateSlug || templateSlug.startsWith('author-') ) {
				setIsUserTemplate(true);
			}
		}
	}, [isSiteEditor] );

	useEffect( () => {
		const {
			getBlockParentsByBlockName,
			getBlocksByClientId
		} = select( 'core/block-editor' );
	
		let repeaterOptions = [];
		let parentNames = [];

		const parentRepeaterClientIds = getBlockParentsByBlockName( clientId, 'acf-field-blocks/acf-repeater' );
		const parentClientIds         = getBlockParentsByBlockName( clientId, ['acf-field-blocks/acf-repeater','acf-field-blocks/acf-term-loop','acf-field-blocks/acf-user-loop'], true );

		if ( parentClientIds.length ) {
			const parentBlocks = getBlocksByClientId( parentClientIds );
			parentNames = parentBlocks.map( block => block.name );

			if ( parentNames.includes('acf-field-blocks/acf-term-loop') ) {
				setIsTermLoopDecendant(true);
			} else {
				if ( "current_term" === attributes.fieldSource ) {
					setAttributes( { fieldSource: "current_post" } );
				}
			}

			if ( parentNames.includes('acf-field-blocks/acf-user-loop') ) {
				setIsUserLoopDecendant(true);
			} else {
				if ( "current_user" === attributes.fieldSource ) {
					setAttributes( { fieldSource: "current_post" } );
				}
			}
		}

		if ( parentRepeaterClientIds.length ) {
			const repeaterParentBlocks = getBlocksByClientId( parentRepeaterClientIds );
			const repeaterParentKeys   = repeaterParentBlocks.map( block => block.attributes.fieldKey );
			if ( ! isLoadingFields ) {
				repeaterOptions = repeaterParentKeys.map( ( parentKey, i ) => {
					const field = getField( parentKey );
					let key = repeaterParentKeys.slice( 0, i + 1 ).join('/');
					return {
						value: `repeater|${key}`,
						label: field?.full_label
					}
				} )
				if ( "" === attributes.fieldSource && 'acf-field-blocks/acf-repeater' === parentNames[0] ) {
					setAttributes( { fieldSource: repeaterOptions[ repeaterOptions.length - 1 ].value } )
				}
			}
		}
		setAncestorOptions(repeaterOptions);

		if ( "" === attributes.fieldSource ) {
			if ( parentNames.length && 'acf-field-blocks/acf-term-loop' === parentNames[0] ) {
				setAttributes( { fieldSource: 'current_term' } )
			} else if ( parentNames.length && 'acf-field-blocks/acf-user-loop' === parentNames[0] ) {
				setAttributes( { fieldSource: 'current_user' } )
			} else {
				setAttributes( { fieldSource: "current_post" } )
			}
		}
		
	}, [isLoadingFields] )

	if ( isLoadingFields ) {
		return (
			<BaseControl label={ __( 'Field Source', 'acf-field-blocks' ) }>
				<div>
					<Spinner />
				</div>
			</BaseControl>
		)
	}
	
	return (
		<SelectControl
			label={ __( 'Field Source', 'acf-field-blocks' ) }
			value={ attributes.fieldSource }
			onChange={ value => setAttributes( { fieldSource: value } ) }
			__nextHasNoMarginBottom={ hasNoMarginBottom }
			help={ help }
		>
			<option value="current_post">{ __( 'Current Post', 'acf-field-blocks' ) }</option>
			{/* <optgroup label={ __( 'User', 'acf-field-blocks' ) }>
				<option value="current_post_author">{ __( 'Current Post Author', 'acf-field-blocks' ) }</option>
				<option value="logged_in_user">{ __( 'Current Logged In User', 'acf-field-blocks' ) }</option>
			</optgroup> */}
			{ ( isTermLoopDecendant || isTermTemplate ) && (
				<option value="current_term">{ __( 'Current Term', 'acf-field-blocks' ) }</option>
			) }
			{ ( isUserLoopDecendant || isUserTemplate ) && (
				<option value="current_user">{ __( 'Current User', 'acf-field-blocks' ) }</option>
			) }
			{ window.ACFFieldBlocks.hasACFOptionPage && (
				<option value="option">{ __( 'Options', 'acf-field-blocks' ) }</option>
			)}
			{ ancestorOptions.length && (
				<>
					{ ancestorOptions.map( option => (
						<option value={ option.value }>Repeater: { option.label }</option>
					) ) }
				</>
			) }
		</SelectControl>
	)
}

export default FieldSourceControl;