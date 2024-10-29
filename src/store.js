import apiFetch from '@wordpress/api-fetch';
import { createReduxStore, register } from '@wordpress/data';

const DEFAULT_STATE = {
	allFieldGroups: [],
	allFields: [],
	values: {}
};

const actions = {
	setAllFieldGroups( value ) {
		return {
			type: 'SET_ALL_FIELD_GROUPS',
			value
		};
	},
	setAllFields( value ) {
		return {
			type: 'SET_ALL_FIELDS',
			value
		};
	},
	setValues( key, value ) {
		return {
			type: 'SET_VALUES',
			key,
			value
		};
	},
	fetchFromAPI( path ) {
		return {
			type: 'FETCH_FROM_API',
			path
		};
	}
};

const store = createReduxStore( 'acf-field-blocks/data', {
	reducer( state = DEFAULT_STATE, action ) {
		if ( 'SET_ALL_FIELD_GROUPS' === action.type ) {
			return {
				...state,
				allFieldGroups: action.value
			};
		} else if ( 'SET_ALL_FIELDS' === action.type ) {
			return {
				...state,
				allFields: action.value
			};
		} else if ( 'SET_VALUES' === action.type ) {
			return {
				...state,
				values: {
					...state.values,
					[action.key]: action.value
				}
			};
		}

		return state;
	},

	actions,

	selectors: {
		getAllFieldGroups( state ) {
			return state.allFieldGroups;
		},
		getAllFields( state ) {
			return state.allFields;
		},
		getValues( state, id = 0 ) {
			return state.values?.[ id ]
		}
	},

	controls: {
		FETCH_FROM_API( action ) {
			return apiFetch({ path: action.path });
		}
	},

	resolvers: {
		*getAllFieldGroups() {
			const path = '/acf-field-blocks/v1/fieldgroups/';
			const response = yield actions.fetchFromAPI( path );
			return actions.setAllFieldGroups( response );
		},
		*getAllFields() {
			const path = '/acf-field-blocks/v1/fields/';
			const response = yield actions.fetchFromAPI( path );
			return actions.setAllFields( response );
		},
		*getValues( id = 0 ) {
			let path = '/acf-field-blocks/v1/values/?id=' + id;
			const response = yield actions.fetchFromAPI( path );
			return actions.setValues( id, response );
		},
	}
});

register( store );