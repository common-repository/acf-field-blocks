import {
	select,
	useSelect
} from '@wordpress/data';
import {
	BaseControl,
	SelectControl,
	Spinner,
	Tip
} from '@wordpress/components';
import {
	useEffect,
	useState
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	getFieldOptions
} from '../../functions';
import './editor.scss';

const FieldKeyControl = ({
	label,
	filterBy = {},
	source,
	value,
	onChange,
	context,
	help = null
}) => {

	const [ fieldSource, setFieldSource ] = useState({});
	const [ repeaterSubField, setRepeaterSubField ] = useState(false);
	const isDescendentOfQueryLoop = Number.isFinite( context?.queryId );
	const isSiteEditor = typeof window.pagenow !== 'undefined' && 'site-editor' === window.pagenow;

	useEffect( () => {
		setRepeaterSubField( source.includes('repeater|') );
		if ( 'current_post' === source ) {
			if ( isDescendentOfQueryLoop ) {
				setFieldSource({
					object: 'post',
					type: context.postType
				})
			} else if ( isSiteEditor ) {
				const siteTemplate = select('core/edit-site').getEditedPostId();
				let templateSlug = '';
				[ , templateSlug ] = siteTemplate.split('//');
				if ( templateSlug ) {
					let postType = false;
					if ( templateSlug.startsWith('single-') ) {
						[ , postType ] = templateSlug.split('-');
					} else if ( 'single' === templateSlug ) {
						postType = 'post'
					} else if ( 'page' === templateSlug ) {
						postType = 'page'
					}
					setFieldSource({
						object: 'post',
						type: postType
					});
				} else {
					setFieldSource({
						object: 'post',
						type: false
					});
				}
			} else {
				const postType = select('core/editor').getCurrentPostType();
				setFieldSource({
					object: 'post',
					type: 'wp_template' !== postType && 'wp_template_part' !== postType ? postType : false
				})
			}
		} else if ( 'current_term' === source ) {
			if ( context?.["acf-field-blocks/taxonomy"] ) {
				setFieldSource({
					object: 'term',
					type: context["acf-field-blocks/taxonomy"]
				})
			} else if ( isSiteEditor ) {
				const siteTemplate = select('core/edit-site').getEditedPostId();
				let templateSlug = '';
				[ , templateSlug ] = siteTemplate.split('//');
				if ( templateSlug ) {
					let taxonomy = false;
					if ( 'category' === templateSlug || templateSlug.startsWith('category-') ) {
						taxonomy = 'category'
					} else if ( 'tag' === templateSlug || templateSlug.startsWith('tag-') ) {
						taxonomy = 'post_tag'
					}
					setFieldSource({
						object: 'term',
						type: taxonomy
					});
				} else {
					setFieldSource({
						object: 'term',
						type: false
					});
				}
			} else {
				setFieldSource({
					object: 'term',
					type: false
				})
			}
		} else if ( 'current_user' === source ) {
			setFieldSource({
				object: 'user',
				type: false
			})
		} else if ( 'option' === source ) {
			setFieldSource({
				object: 'option',
				type: false
			});
		} else if ( source.includes('|') ) {
			const [ obj, type ] = source.split('|');
			setFieldSource({
				object: obj,
				type: type
			});
		}
	}, [source,isDescendentOfQueryLoop,context,isSiteEditor] );

	const {
		fieldOptions,
		isNoOptions,
		isLoadingOptions
	} = getFieldOptions( fieldSource, filterBy );

	if ( isLoadingOptions ) {
		return (
			<BaseControl label={ label }>
				<div>
					<Spinner />
				</div>
			</BaseControl>
		)
	}

	if ( isNoOptions ) {
		return (
			<SelectControl
				label={ ! repeaterSubField ? label : 'Sub Field Name' }
				value={ '' }
				options={ [
					{
						value: '',
						label: __( 'No field available', 'acf-field-blocks' )
					}
				] }
				disabled={ true }
			/>
		)
	}

	if ( repeaterSubField ) {
		return (
			<SelectControl
				label={ __( 'Sub Field Name', 'acf-field-blocks' ) }
				value={ value }
				onChange={ onChange }
				help={ help }
			>
				{ fieldOptions.map( group => (
					<>
						{ ! group.fields ? (
							<option value={ group.value } disabled={ group.disabled }>{ group.label }</option>
						) : (
							<>
								{ group.fields?.map( field => (
									<option value={ field.value }>{ field.label }</option>
								) ) }
							</>
						) }
					</>
				) ) }
			</SelectControl>
		)
	}

	return (
		<SelectControl
			label={ label }
			value={ value }
			onChange={ onChange }
			help={ help }
		>
			{ fieldOptions.map( group => (
				<>
					{ ! group.fields ? (
						<option value={ group.value } disabled={ group.disabled }>{ group.label }</option>
					) : (
						<optgroup label={ group.title }>
							{ group.fields?.map( field => (
								<option value={ field.value }>{ field.label }</option>
							) ) }
						</optgroup>
					) }
				</>
			) ) }
		</SelectControl>
	)
}

export default FieldKeyControl;