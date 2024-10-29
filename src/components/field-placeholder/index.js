import './editor.scss';

const FieldPlaceholder = ({
	label,
	isSelected,
	children
}) => {

	if ( ! isSelected ) {
		return label;
	}

	return (
		<div className="acf-field-placeholder">
			<div className="acf-field-placeholder__label">{ label }</div>
			<div className="acf-field-placeholder__fieldset">{ children }</div>
		</div>
	)
}

export default FieldPlaceholder;