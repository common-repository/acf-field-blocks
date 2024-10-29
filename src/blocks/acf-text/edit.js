/**
 * External depedencies
 */
import classnames from 'classnames';
import { isArray } from 'lodash';

/**
 * WordPress dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	useBlockProps,
	InspectorControls
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	useEffect,
	useState,
	createElement
} from '@wordpress/element';
import {
	Flex,
	FlexBlock,
	BaseControl,
	ToggleControl,
	PanelBody,
	TextControl,
	Spinner,
	ToolbarDropdownMenu,
	Disabled,
	SelectControl
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	FieldSourceControl,
	FieldKeyControl,
	FieldPlaceholder,
} from '../../components'
import {
	useFieldsLoader
} from '../../functions'

const disabledClickProps = {
	onClick: ( event ) => event.preventDefault(),
	'aria-disabled': true,
};

export default function Edit( {
	clientId,
	attributes: {
		fieldKey,
		fieldSource,
		showMessageIfEmpty,
		textAlign,
		tag,
		prefix,
		suffix,
		emptyMessage,
		checkedText,
		uncheckedText,
		separator,
		returnFormat,
		linkToObject,
		newTab
	},
	attributes,
	setAttributes,
	isSelected,
	context
} ) {

	const [ fieldValue, setFieldValue ]             = useState('');
	const [ fieldLabel, setFieldLabel ]             = useState('');
	const [ fieldType, setFieldType ]               = useState('');
	const [ formatOptions, setFormatOptions ]       = useState([]);
	const [ isMultipleField, setIsMultipleField ]   = useState(false);
	const [ formattedValue, setFormattedValue ]     = useState('ACF Text');
	const [ hasValue, setHasValue ]                 = useState(false);
	const TagName    = tag;
	const tagOptions = ["h1","h2","h3","h4","h5","h6","p","span","div","section","pre","code"];

	const {
		getField,
		getValue,
		isLoadingFields,
		isLoadingValues,
		hasContext
	} = useFieldsLoader( fieldSource, context );

	useEffect( () => {
		if ( fieldKey ) {
			if ( ! isLoadingFields ) {
				const field = getField( fieldKey );
				setFieldType( field?.type );
				setFieldLabel( field?.full_label );
				setIsMultipleField( field?.multiple );
			}
			if ( ! isLoadingValues ) {
				const value = getValue( fieldKey );
				setFieldValue( value );
			}
		}
	}, [ fieldSource, fieldKey, isLoadingFields, isLoadingValues, returnFormat] );

	useEffect( () => {
		if ( ['select','checkbox','radio','button_group'].includes( fieldType ) ) {
			setFormatOptions([
				{
					value: 'value',
					label: 'Value'
				},
				{
					value: 'label',
					label: 'Label'
				}
			]);
		} else {
			setFormatOptions([]);
		}
	}, [fieldType] );

	const linkToObjectLabel = (fieldType) => {
		if ( 'email' === fieldType ) {
			return __( 'Link to email', 'acf-field-blocks' );
		} else if ( 'url' === fieldType ) {
			return __( 'Create link', 'acf-field-blocks' );
		} else if ( 'post_object' === fieldType || 'relationship' === fieldType ) {
			return __( 'Link to post', 'acf-field-blocks' );
		} else if ( 'user' === fieldType || 'taxonomy' === fieldType ) {
			return __( 'Link to archive page', 'acf-field-blocks' );
		}
	}
	
	const blockProps = useBlockProps({
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	});

	useEffect( () => {
		if ( fieldLabel ) {
			setFormattedValue( 'ACF Text: ' + fieldLabel);
		} else {
			setFormattedValue( 'ACF Text' );
		}

		if ( hasContext ) {
			if ( fieldValue ) {
				if ( "true_false" === fieldType && checkedText ) {
					setFormattedValue( checkedText );
				} else if ( "wysiwyg" === fieldType ) {
					let content = <Disabled dangerouslySetInnerHTML={{ __html: fieldValue }} />
					setFormattedValue( content );
				} else if ( "link" === fieldType && fieldValue?.title ) {
					setFormattedValue( fieldValue.title );
				} else if ( "post_object" === fieldType || "relationship" === fieldType ) {
					if ( isMultipleField && isArray(fieldValue) ) {
						setFormattedValue( fieldValue.map( val => val?.post_title ?? val ) );
					} else if ( fieldValue.post_title ) {
						setFormattedValue( fieldValue.post_title );
					}
				} else if ( "taxonomy" === fieldType ) {
					if ( isMultipleField && isArray(fieldValue) ) {
						setFormattedValue( fieldValue.map( val => val?.name ?? val ) );
					} else if ( fieldValue.name ) {
						setFormattedValue( fieldValue.name );
					}
				} else if ( "user" === fieldType ) {
					if ( isMultipleField && isArray(fieldValue) ) {
						setFormattedValue( fieldValue.map( val => val?.display_name ?? val ) );
					} else if ( fieldValue.display_name ) {
						setFormattedValue( fieldValue.display_name );
					}
				} else if ( ['select','checkbox','radio','button_group'].includes( fieldType ) ) {
					if ( isMultipleField && isArray(fieldValue) ) {
						setFormattedValue( fieldValue.map( val => val?.[returnFormat] ?? val ) );
					} else {
						setFormattedValue( fieldValue?.[returnFormat] );
					}
				} else {
					setFormattedValue( fieldValue );
				}
				setHasValue(true);
			} else if ( "true_false" === fieldType && uncheckedText ) {
				setFormattedValue( uncheckedText );
				setHasValue(true);
			} else {
				setHasValue(false);
			}
		}
	}, [
		hasContext,
		fieldValue,
		fieldLabel,
		fieldType,
		emptyMessage,
		checkedText,
		uncheckedText,
		prefix,
		suffix,
		showMessageIfEmpty,
		returnFormat,
		formatOptions,
		separator
	] );

	if ( ! fieldKey ) {
		return (
			<div { ...blockProps }>
				{ ! isSelected ? formattedValue : (
					<FieldPlaceholder
						label={ __( 'ACF Text', 'acf-field-blocks' ) }
						isSelected
					>
						<FieldSourceControl
							attributes={ attributes }
							setAttributes={ setAttributes }
							clientId={ clientId }
							hasNoMarginBottom={ true }
							context={ context }
							help={ __( 'Select the object where the field is attached.', 'acf-field-blocks' ) }
						/>
						<FieldKeyControl
							label={ __( "Field Name", "acf-field-blocks" ) }
							filterBy={ {
								return: "text"
							} }
							source={ fieldSource }
							value={ fieldKey }
							onChange={ fieldKey => setAttributes( { fieldKey } ) }
							context={ context }
							help={ __( 'Select a custom field to load', 'acf-field-blocks' ) }
						/>
					</FieldPlaceholder>
				) }
			</div>
		)
	}

	return (
		<>
			{ "wysiwyg" !== fieldType && (
				<BlockControls group="block">
					<ToolbarDropdownMenu
						icon={ createElement( 'strong', { style:{fontFamily:"sans-serif",fontWeight:800,fontSize:"18px",textAlign:"center",letterSpacing:"-1px" } }, tag.toUpperCase() ) }
						label="Tag"
						controls={ tagOptions.map( tagOption => {
							return {
								title: createElement( 'strong', { style:{fontFamily:"sans-serif",fontWeight:800,fontSize:"14px",textAlign:"center",letterSpacing:"-1px" } }, tagOption.toUpperCase() ),
								onClick: () => setAttributes( { tag: tagOption } )
							}
						} ) }
					/>
					<AlignmentControl
						value={ textAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { textAlign: nextAlign } );
						} }
					/>
				</BlockControls>
			) }

			<InspectorControls>
				<PanelBody
					title={ __( 'Field Settings', 'acf-field-blocks' ) }
				>
					<FieldSourceControl
						attributes={ attributes }
						setAttributes={ setAttributes }
						clientId={ clientId }
						help={ __( 'Select the object where the field is attached.', 'acf-field-blocks' ) }
						context={ context }
					/>
					<FieldKeyControl
						label={ __( "Field Name", "acf-field-blocks" ) }
						filterBy={ {
							return: "text"
						} }
						source={ fieldSource }
						value={ fieldKey }
						onChange={ fieldKey => setAttributes( { fieldKey } ) }
						context={ context }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Output Settings', 'acf-field-blocks' ) }
				>
					{ ["url","email",'post_object',"relationship","taxonomy","user"].includes(fieldType) && (
						<ToggleControl
							label={ linkToObjectLabel(fieldType) }
							checked={ linkToObject }
							onChange={ value => setAttributes( { linkToObject: value } ) }
						/>
					) }
					{ ( ["link","page_link"].includes(fieldType) || ( ['url','email','post_object',"relationship","taxonomy","user"].includes(fieldType) && linkToObject ) ) && (
						<ToggleControl
							label={ __( "Open in new tab", "acf-field-blocks" ) }
							checked={ newTab }
							onChange={ value => setAttributes( { newTab: value } ) }
						/>
					) }
					{ ['select','checkbox','radio','button_group'].includes( fieldType ) && (
						<SelectControl
							label={ __( 'Return Format', 'acf-field-blocks' ) }
							value={ returnFormat }
							options={ [
								{
									value: 'value',
									label: 'Value'
								},
								{
									value: 'label',
									label: 'Label'
								}
							] }
							onChange={ returnFormat => setAttributes( { returnFormat } ) }
						/>
					) }
					{ isMultipleField && (
						<TextControl
							label={ __( 'Values Separator', 'acf-field-blocks' ) }
							value={ separator }
							onChange={ separator => setAttributes( { separator } ) }
						/>
					) }
					{ "true_false" !== fieldType && "wysiwyg" !== fieldType && (
						<BaseControl>
							<Flex alignment='top'>
								<FlexBlock>
									<TextControl
										label={ __( 'Prefix', 'acf-field-blocks' ) }
										value={ prefix }
										onChange={ prefix => setAttributes( { prefix } ) }
									/>
								</FlexBlock>
								<FlexBlock>
									<TextControl
										label={ __( 'Suffix', 'acf-field-blocks' ) }
										value={ suffix }
										onChange={ suffix => setAttributes( { suffix } ) }
									/>
								</FlexBlock>
							</Flex>
						</BaseControl>
					) }
					{ "true_false" !== fieldType && (
						<ToggleControl
							label={ __( "Show message if empty", "acf-field-blocks" ) }
							checked={ showMessageIfEmpty }
							onChange={ value => setAttributes( { showMessageIfEmpty: value } ) }
						/>
					) }
					{ showMessageIfEmpty && "true_false" !== fieldType && (
						<TextControl
							label={ __( 'Empty Message', 'acf-field-blocks' ) }
							value={ emptyMessage }
							onChange={ emptyMessage => setAttributes( { emptyMessage } ) }
							help={ __( 'This message will be displayed if the field has no value', 'acf-field-blocks' ) }
						/>
					) }
					{ "true_false" === fieldType && (
						<TextControl
							label={ __( 'Checked Message', 'acf-field-blocks' ) }
							value={ checkedText }
							onChange={ checkedText => setAttributes( { checkedText } ) }
							help={ __( 'Text shown when the field is checked', 'acf-field-blocks' ) }
						/>
					) }
					{ "true_false" === fieldType && (
						<TextControl
							label={ __( 'Unchecked Message', 'acf-field-blocks' ) }
							value={ uncheckedText }
							onChange={ uncheckedText => setAttributes( { uncheckedText } ) }
							help={ __( 'Text shown when the field is unchecked', 'acf-field-blocks' ) }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			{ isLoadingFields || isLoadingValues ? (
				<TagName { ...blockProps }>
					<Spinner />
				</TagName>
			) : ( 
				<TagName { ...blockProps }>
					{ hasValue ? (
						<>
							{ "true_false" !== fieldType && "wysiwyg" !== fieldType && prefix }
							{ isArray( formattedValue ) ? (
								( 'link' === fieldType || 'page_link' === fieldType || linkToObject ) ? formattedValue.map( ( val, i ) => {
									return (
										<>
											{ i > 0 && separator }
											<a
												href="#"
												{...disabledClickProps}
											>
												{ val }
											</a>
										</>
									)
								} ) : formattedValue.join(separator)
							) : (
								( 'link' === fieldType || 'page_link' === fieldType || linkToObject ) ? (
									<a
										href="#"
										{...disabledClickProps}
									>
										{ formattedValue }
									</a>
								) : formattedValue
							) }
							{ "true_false" !== fieldType && "wysiwyg" !== fieldType && suffix }
						</>
					) : (
						<>
							{ ( hasContext && showMessageIfEmpty && emptyMessage ) ? emptyMessage : formattedValue }
						</>
					)
				}
					
				</TagName>
			) }
			
			
		</>
	);
};