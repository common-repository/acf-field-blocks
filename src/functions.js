import { __ } from '@wordpress/i18n';
import {
	useSelect,
	select
} from '@wordpress/data';

export const useFieldsLoader = (fieldSource, context = false) => {
	let postId = 0;
	let source = "current_post";

	if ( fieldSource.includes('|') ) {
		const [ type, parentField ] = fieldSource.split('|');
		if ( context?.["acf-field-blocks/repeaters"] ) {
			let parent = context["acf-field-blocks/repeaters"].find( ancestor => ancestor.key === parentField );
			if ( parent?.source ) {
				source = parent?.source;
			}
		}
	} else {
		source = fieldSource;
	}

	if ( 'current_post' === source ) {
		if ( Number.isFinite( context?.queryId ) ) {
			postId = context?.postId;
		} else {
			let editId = select('core/editor').getCurrentPostId();
			if ( Number.isFinite( editId ) ) {
				postId = editId;
			}
		}
	} else if ( 'current_term' === source ) {
		if ( context?.["acf-field-blocks/term"] ) {
			postId = `${context["acf-field-blocks/term"]?.taxonomy}_${context["acf-field-blocks/term"]?.term_id}`;
		}
	} else if ( 'current_user' === source ) {
		if ( context?.["acf-field-blocks/user"] ) {
			postId = `user_${context["acf-field-blocks/user"]?.ID}`;
		}
	} else if ( 'option' === source ) {
		postId = 'option';
	}
	
	return useSelect( select => {
		const {
			getAllFields,
			getValues,
			isResolving
		} = select( 'acf-field-blocks/data' );

		const fields = getAllFields();
		const values = postId ? getValues(postId) : {};

		return {
			getField: (fieldKey) => {
				return fields?.[fieldKey];
			},
			getValue: fieldKey => {
				if ( context?.["acf-field-blocks/repeaters"] ) {
					let ancestorValues = {};
					context["acf-field-blocks/repeaters"].map( ancestor => {
						if ( ancestorValues?.[ ancestor.key ] ) {
							ancestorValues = ancestorValues?.[ ancestor.key ]?.[ ancestor.index ];
						} else {
							ancestorValues = values?.[ ancestor.key ]?.[ ancestor.index ];
						}
					} );
					return ancestorValues?.[fieldKey];
				}
				return values?.[fieldKey];
			},
			isLoadingFields: isResolving?.( 'getAllFields' ),
			isLoadingValues: isResolving?.( 'getValues', [ postId ] ),
			hasContext: postId
		};
	}, [postId] )
};

export function getFieldOptions( source, filterBy = {} ) {
	let fieldOptions = [];
	const {
		allFieldGroups,
		isLoadingAllFieldGroups
	} = useSelect( select => {
		const {
			getAllFieldGroups,
			isResolving
		} = select( 'acf-field-blocks/data' );
	
		return {
			allFieldGroups: getAllFieldGroups(),
			isLoadingAllFieldGroups: isResolving?.( 'getAllFieldGroups' )
		};
	}, []);

	allFieldGroups.map( fieldGroup => {
		let options = [];
		if ( fieldGroup.fields?.length ) {
			if ( 'repeater' === source?.object && source.type ) {
				const keys = source.type.split('/');
				let repeaterField = fieldGroup.fields.find( field => field.key === keys[0] );
				if ( repeaterField ) {
					if ( 1 < keys.length ) {
						for ( let i = 1; i < keys.length; i++ ) {
							repeaterField = repeaterField.sub_fields.find( sub_field => sub_field.key === keys[i] );
						}
					}
					repeaterField.sub_fields.map( field => {
						if ( isFieldFiltered( field, filterBy ) ) {
							options.push({
								value: field.key,
								label: field.label
							})
						} 
					} )
				}
			} else if ( isFieldGroupEligible( fieldGroup.object_types, source?.object, source?.type ?? false ) ) {
				fieldGroup.fields.map( field => {
					if ( isFieldFiltered( field, filterBy ) ) {
						options.push({
							value: field.key,
							label: field.label
						})
					} 
				} )
			}
		}
		if ( options.length ) {
			fieldOptions.push({
				title: fieldGroup.title,
				fields: options
			})
		}
	} )

	return {
		isNoOptions: 0 === fieldOptions.length,
		fieldOptions: [
			{
				value: '',
				label: __( "Select a field", "acf-field-blocks" ),
				disabled: true
			},
			...fieldOptions
		],
		isLoadingOptions: isLoadingAllFieldGroups
	}
}

export function isFieldGroupEligible( objectTypes, obj, type ) {
	for ( let i = 0; i < objectTypes.length; i++ ) {
		if ( false !== type ) {
			if ( obj === objectTypes[i].type && ( type === objectTypes[i].subtype || 'all' === objectTypes[i].subtype ) ) {
				return true;
			}
		} else {
			if ( obj === objectTypes[i].type ) {
				return true;
			}
		}
	}

	return false;
}

export function isFieldFiltered( field, filterBy = {} ) {
	if ( 0 === Object.keys( filterBy ).length ) {
		return true;
	}

	let valid = true;
	for (const [key, value] of Object.entries(filterBy)) {
		if ( 'return' === key ) {
			valid = valid && isFieldHasReturn( field, value );
		} else if ( 'multiple' === key ) {
			if ( value ) {
				valid = valid && isFieldHasMultipleReturn( field );
			} else {
				valid = valid && ! isFieldHasMultipleReturn( field );
			}
		} else if ( 'type' === key ) {
			valid = valid && value.includes( field.type );
		}
	}
	return valid;
}

export function isFieldHasReturn( field, type ) {
	if ( 'text' === type && ! ['image','file','oembed','gallery','google_map','message','accordion','tab','group','repeater','flexible_content','clone'].includes(field.type) ) {
		return true;
	} else if ( 'image' === type && 'image' === field.type ) {
		return true;
	} else if ( 'link' === type && ['email','url','image','file','link','page_link'].includes(field.type) ) {
		return true;
	} else if ( 'repeater' === type && 'repeater' === field.type ) {
		return true;
	}
	return false;
}

export function isFieldHasMultipleReturn( field ) {
	if ( 'checkbox' === field.type ) {
		return true;
	} else if ( 'relationship' === field.type ) {
		return true;
	} else if ( 'taxonomy' === field.type && ( "checkbox" === field.field_type || "multi_select" === field.field_type ) ) {
		return true;
	} else if ( field.hasOwnProperty('multiple') ) {
		return field.multiple;
	}
	return false;
}

export function removeAnchorTag( value ) {
	// To do: Refactor this to use rich text's removeFormat instead.
	return value.toString().replace( /<\/?a[^>]*>/g, '' );
}