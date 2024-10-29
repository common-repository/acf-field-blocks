/**
 * External depedencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	AlignmentControl,
	BlockControls,
	useBlockProps,
	InspectorControls,
	RichText,
	__experimentalUseBorderProps as useBorderProps,
	__experimentalUseColorProps as useColorProps,
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	// __experimentalGetShadowClassesAndStyles as useShadowProps,
	getTypographyClassesAndStyles,
	__experimentalGetElementClassName
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import {
	useSelect
} from '@wordpress/data';
import {
	useEffect,
	useState,
	useRef
} from '@wordpress/element';
import {
	BaseControl,
	ButtonGroup,
	Button,
	ToggleControl,
	PanelBody,
	Spinner,
	SelectControl,
	__experimentalToolsPanelItem as ToolsPanelItem
} from '@wordpress/components';
import {
	justifyLeft,
	justifyCenter,
	justifyRight
} from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	FieldSourceControl,
	FieldKeyControl,
	FieldPlaceholder,
} from '../../components'
import {
	useFieldsLoader,
	removeAnchorTag
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
		buttonAlign,
		textAlign,
		textSource,
		customText,
		textFieldKey,
		linkTarget,
		style,
		width,
		asDownload,
		nofollow
	},
	attributes,
	setAttributes,
	isSelected,
	className,
	context
} ) {

	const [ fieldValue, setFieldValue ] = useState('#');
	const [ textFieldValue, setTextFieldValue ] = useState('');
	const [ fieldType, setFieldType ]   = useState('');
	const [ fieldPlaceholder, setFieldPlaceholder ] = useState('ACF Button Link');
	const richTextRef = useRef();

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
			}
			if ( ! isLoadingValues ) {
				const value = getValue( fieldKey );
				setFieldValue( value );
				if ( textFieldKey ) {
					const textValue = getValue( textFieldKey );
					setTextFieldValue( textValue );
				}
			}
		}
	}, [ fieldKey, isLoadingFields, isLoadingValues, textFieldKey ] );

	const {
		media
	} = useSelect( ( select ) => {
		const { getMedia } = select( coreStore );

		return {
			media: ( 'image' === fieldType || 'file' === fieldType ) && fieldValue && getMedia( fieldValue, {
				context: 'view',
			} )
		};
	}, [ fieldValue, fieldType ] );

	useEffect( () => {
		setFieldPlaceholder( 'ACF Button Link' );

		if ( hasContext ) {
			if ( 'link' === textSource && fieldValue ) {
				if ( 'link' === fieldType ) {
					setFieldPlaceholder( fieldValue?.url );
				} else if ( 'page_link' === fieldType ) {
					if ( isNaN( fieldValue ) ) {
						setFieldPlaceholder( fieldValue );
					}
				} else if ( 'file' === fieldType || 'image' === fieldType ) {
					if ( media ) {
						setFieldPlaceholder( media?.source_url );
					}
				} else {
					setFieldPlaceholder( fieldValue );
				}
				
				// setFieldPlaceholder( fieldValue );
			} else if ( 'field' === textSource && textFieldValue ) {
				setFieldPlaceholder( textFieldValue );
			}
		}
	}, [
		fieldValue,
		textFieldValue,
		fieldType,
		media,
		textSource
	] );
	
	const borderProps     = useBorderProps( attributes );
	const colorProps      = useColorProps( attributes );
	const spacingProps    = useSpacingProps( attributes );
	// const shadowProps     = useShadowProps( attributes );
	const typographyProps = getTypographyClassesAndStyles( attributes );
	const blockProps      = useBlockProps({
		className: classnames( {
			[ `has-custom-width wp-block-acf-field-blocks-acf-button__width-${ width }` ]: width,
			[ `has-text-align-${ buttonAlign }` ]: buttonAlign,
		} ),
	});

	if ( ! fieldKey ) {
		return (
			<div { ...blockProps }>
				<FieldPlaceholder
					label={ __( 'ACF Button Link', 'acf-field-blocks' ) }
					isSelected={ isSelected }
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
							return: "link",
							multiple: false
						} }
						source={ fieldSource }
						value={ fieldKey }
						onChange={ fieldKey => setAttributes( { fieldKey } ) }
						context={ context }
						help={ __( 'Select a custom field with link to load', 'acf-field-blocks' ) }
					/>
				</FieldPlaceholder>
			</div>
		)
	}

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					label={ __( 'Align button', 'acf-field-blocks' ) }
					alignmentControls={ [
						{
							icon: justifyLeft,
							title: __( 'Align button left' ),
							align: 'left',
						},
						{
							icon: justifyCenter,
							title: __( 'Align button center' ),
							align: 'center',
						},
						{
							icon: justifyRight,
							title: __( 'Align button right' ),
							align: 'right',
						},
					] }
					value={ buttonAlign }
					onChange={ buttonAlign => {
						setAttributes( { buttonAlign } );
					} }
				/>
				<AlignmentControl
					value={ textAlign }
					onChange={ ( nextAlign ) => {
						setAttributes( { textAlign: nextAlign } );
					} }
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Field Settings', 'acf-field-blocks' ) }
				>
					<FieldSourceControl
						attributes={ attributes }
						setAttributes={ setAttributes }
						clientId={ clientId }
						context={ context }
						help={ __( 'Select the object where the field is attached.', 'acf-field-blocks' ) }
					/>
					<FieldKeyControl
						label={ __( "Field Name", "acf-field-blocks" ) }
						filterBy={ {
							return: "link",
							multiple: false
						} }
						source={ fieldSource }
						value={ fieldKey }
						onChange={ fieldKey => setAttributes( { fieldKey } ) }
						context={ context }
						help={ __( 'Only fields that return text are available.', 'acf-field-blocks' ) }
					/>
					<SelectControl
						label={ __( 'Link Text', 'acf-field-blocks' ) }
						value={ textSource }
						onChange={ textSource => setAttributes( { textSource } ) }
						options={ [
							{
								value: 'link',
								label: __( 'Use the URL', 'acf-field-blocks' )
							},
							{
								value: 'field',
								label: __( 'Load from another field', 'acf-field-blocks' )
							},
							{
								value: 'custom',
								label: __( 'Custom text', 'acf-field-blocks' )
							}
						] }
					/>
					{ 'field' === textSource && (
						<FieldKeyControl
							label={ __( "Text Field Name", "acf-field-blocks" ) }
							filterBy={ {
								type: ["text"]
							} }
							source={ fieldSource }
							value={ textFieldKey }
							onChange={ textFieldKey => setAttributes( { textFieldKey } ) }
							context={ context }
							help={ __( 'Only fields that return text are available.', 'acf-field-blocks' ) }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Link Settings', 'acf-field-blocks' ) }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open in new tab', 'acf-field-blocks' ) }
						onChange={ ( value ) =>
							setAttributes( {
								linkTarget: value ? '_blank' : '_self',
							} )
						}
						checked={ linkTarget === '_blank' }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Mark as nofollow', 'acf-field-blocks' ) }
						onChange={ nofollow => setAttributes( { nofollow } ) }
							checked={ nofollow }
					/>
					{ ( "image" === fieldType || "file" === fieldType ) && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Set as download link', 'acf-field-blocks' ) }
							onChange={ asDownload => setAttributes( { asDownload } ) }
							checked={ asDownload }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<InspectorControls group="dimensions">
				<ToolsPanelItem
					hasValue={ () => !! width }
					label={ __( 'Button Width', 'acf-field-blocks' ) }
					onDeselect={ () => {
						setAttributes( {
							width: undefined
						} )
					} }
					resetAllFilter={ () => ( {
						width: undefined,
					} ) }
					isShownByDefault={ true }
					panelId={ clientId }
				>
					<BaseControl label={ __( 'Button Width', 'acf-field-blocks' ) }>
						<div>
							<ButtonGroup aria-label={ __( 'Button width' ) }>
								{ [ 25, 50, 75, 100 ].map( ( widthValue ) => {
									return (
										<Button
											key={ widthValue }
											size="small"
											variant={
												widthValue === width
													? 'primary'
													: undefined
											}
											onClick={ () => {
												// Check if we are toggling the width off
												const newWidth = width === widthValue ? undefined : widthValue;

												// Update attributes.
												setAttributes( { width: newWidth } );
											} }
										>
											{ widthValue }%
										</Button>
									);
								} ) }
							</ButtonGroup>
						</div>
					</BaseControl>
				</ToolsPanelItem>
			</InspectorControls>

			{ isLoadingFields ? (
				<div { ...blockProps }>
					<Spinner />
				</div>
			) : ( 
				<div { ...blockProps }>
					{ 'custom' === textSource ? (
						<RichText
							ref={ richTextRef }
							aria-label={ __( 'Button text' ) }
							placeholder={ __( 'Add text…' ) }
							value={ customText }
							onChange={ ( value ) =>
								setAttributes( {
									customText: removeAnchorTag( value ),
								} )
							}
							withoutInteractiveFormatting
							className={ classnames(
								className,
								'wp-block-acf-field-blocks-acf-button__link',
								colorProps.className,
								borderProps.className,
								typographyProps.className,
								{
									[ `has-text-align-${ textAlign }` ]: textAlign,
									// For backwards compatibility add style that isn't
									// provided via block support.
									'no-border-radius': style?.border?.radius === 0,
								},
								__experimentalGetElementClassName( 'button' )
							) }
							style={ {
								...borderProps.style,
								...colorProps.style,
								...spacingProps.style,
								// ...shadowProps.style,
								...typographyProps.style,
							} }
							identifier="text"
						/>
					) : (
						<a
							href="#"
							{...disabledClickProps}
							className={ classnames(
								className,
								'wp-block-acf-field-blocks-acf-button__link',
								colorProps.className,
								borderProps.className,
								typographyProps.className,
								{
									[ `has-text-align-${ textAlign }` ]: textAlign,
									// For backwards compatibility add style that isn't
									// provided via block support.
									'no-border-radius': style?.border?.radius === 0,
								},
								__experimentalGetElementClassName( 'button' )
							) }
							style={ {
								...borderProps.style,
								...colorProps.style,
								...spacingProps.style,
								// ...shadowProps.style,
								...typographyProps.style,
							} }
						>{ fieldPlaceholder }</a>
					) }
				</div>
			) }
			
			
		</>
	);
};