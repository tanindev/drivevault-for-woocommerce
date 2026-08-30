import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const STORE_NAME = 'drivevault/drive';

const DEFAULT_STATE = {
	files: [],
	currentFolderId: 'root',
	breadcrumbs: [ { id: 'root', name: 'My Drive' } ],
	searchQuery: '',
	filterType: 'all',
	selectedFiles: [],
	isLoading: false,
	error: null,
	connectionStatus: {
		is_connected: window.drivevaultData?.isConnected || false,
		account_email: '',
	},
	quota: null,
	settings: null,
};

const actions = {
	setCurrentFolder( folderId, folderName ) {
		return {
			type: 'SET_CURRENT_FOLDER',
			folderId,
			folderName,
		};
	},
	navigateToBreadcrumb( index ) {
		return {
			type: 'NAVIGATE_BREADCRUMB',
			index,
		};
	},
	setSearchQuery( query ) {
		return {
			type: 'SET_SEARCH_QUERY',
			query,
		};
	},
	setFilterType( filterType ) {
		return {
			type: 'SET_FILTER_TYPE',
			filterType,
		};
	},
	toggleSelectFile( file ) {
		return {
			type: 'TOGGLE_SELECT_FILE',
			file,
		};
	},
	clearSelection() {
		return {
			type: 'CLEAR_SELECTION',
		};
	},
	setFiles( files ) {
		return {
			type: 'SET_FILES',
			files,
		};
	},
	setLoading( isLoading ) {
		return {
			type: 'SET_LOADING',
			isLoading,
		};
	},
	setError( error ) {
		return {
			type: 'SET_ERROR',
			error,
		};
	},
	setConnectionStatus( status ) {
		return {
			type: 'SET_CONNECTION_STATUS',
			status,
		};
	},
	setQuota( quota ) {
		return {
			type: 'SET_QUOTA',
			quota,
		};
	},
	setSettings( settings ) {
		return {
			type: 'SET_SETTINGS',
			settings,
		};
	},

	// Async action thunks
	fetchFiles( params = {} ) {
		return async ( { dispatch, select } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			const state = select.getState();
			const folderId =
				params.folderId !== undefined
					? params.folderId
					: state.currentFolderId;
			const search =
				params.search !== undefined ? params.search : state.searchQuery;
			const filterType =
				params.filterType !== undefined
					? params.filterType
					: state.filterType;
			const refresh = !! params.refresh;

			try {
				const queryParams = new URLSearchParams( {
					folder_id: folderId,
					search,
					filter_type: filterType,
					refresh: refresh ? '1' : '0',
				} );

				const response = await apiFetch( {
					path: `drivevault/v1/drive/files?${ queryParams.toString() }`,
				} );

				dispatch.setFiles( response.files || [] );
			} catch ( err ) {
				dispatch.setError( err.message || 'Failed to fetch files' );
				dispatch.setFiles( [] );
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	fetchStatus() {
		return async ( { dispatch } ) => {
			try {
				const status = await apiFetch( {
					path: 'drivevault/v1/auth/status',
				} );
				dispatch.setConnectionStatus( status );
			} catch ( err ) {
				console.error( 'Failed to fetch status', err );
			}
		};
	},

	fetchQuota() {
		return async ( { dispatch } ) => {
			try {
				const quota = await apiFetch( {
					path: 'drivevault/v1/drive/quota',
				} );
				dispatch.setQuota( quota );
			} catch ( err ) {
				console.error( 'Failed to fetch quota', err );
			}
		};
	},

	fetchSettings() {
		return async ( { dispatch } ) => {
			try {
				const settings = await apiFetch( {
					path: 'drivevault/v1/settings',
				} );
				dispatch.setSettings( settings );
			} catch ( err ) {
				console.error( 'Failed to fetch settings', err );
			}
		};
	},

	saveSettings( newSettings ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			try {
				const response = await apiFetch( {
					path: 'drivevault/v1/settings',
					method: 'POST',
					data: newSettings,
				} );
				dispatch.setSettings( response.settings );
				return response;
			} finally {
				dispatch.setLoading( false );
			}
		};
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'SET_CURRENT_FOLDER': {
			const exists = state.breadcrumbs.some(
				( b ) => b.id === action.folderId
			);
			let newBreadcrumbs = [ ...state.breadcrumbs ];
			if ( ! exists && action.folderName ) {
				newBreadcrumbs.push( {
					id: action.folderId,
					name: action.folderName,
				} );
			}
			return {
				...state,
				currentFolderId: action.folderId,
				breadcrumbs: newBreadcrumbs,
				searchQuery: '',
			};
		}
		case 'NAVIGATE_BREADCRUMB': {
			const slice = state.breadcrumbs.slice( 0, action.index + 1 );
			const target = slice[ slice.length - 1 ] || {
				id: 'root',
				name: 'My Drive',
			};
			return {
				...state,
				currentFolderId: target.id,
				breadcrumbs: slice,
				searchQuery: '',
			};
		}
		case 'SET_SEARCH_QUERY':
			return { ...state, searchQuery: action.query };
		case 'SET_FILTER_TYPE':
			return { ...state, filterType: action.filterType };
		case 'TOGGLE_SELECT_FILE': {
			const exists = state.selectedFiles.some(
				( f ) => f.id === action.file.id
			);
			return {
				...state,
				selectedFiles: exists
					? state.selectedFiles.filter(
							( f ) => f.id !== action.file.id
					  )
					: [ ...state.selectedFiles, action.file ],
			};
		}
		case 'CLEAR_SELECTION':
			return { ...state, selectedFiles: [] };
		case 'SET_FILES':
			return { ...state, files: action.files };
		case 'SET_LOADING':
			return { ...state, isLoading: action.isLoading };
		case 'SET_ERROR':
			return { ...state, error: action.error };
		case 'SET_CONNECTION_STATUS':
			return { ...state, connectionStatus: action.status };
		case 'SET_QUOTA':
			return { ...state, quota: action.quota };
		case 'SET_SETTINGS':
			return { ...state, settings: action.settings };
		default:
			return state;
	}
};

const selectors = {
	getState( state ) {
		return state;
	},
	getFiles( state ) {
		return state.files;
	},
	getCurrentFolderId( state ) {
		return state.currentFolderId;
	},
	getBreadcrumbs( state ) {
		return state.breadcrumbs;
	},
	getSearchQuery( state ) {
		return state.searchQuery;
	},
	getFilterType( state ) {
		return state.filterType;
	},
	getSelectedFiles( state ) {
		return state.selectedFiles;
	},
	isLoading( state ) {
		return state.isLoading;
	},
	getError( state ) {
		return state.error;
	},
	getConnectionStatus( state ) {
		return state.connectionStatus;
	},
	getQuota( state ) {
		return state.quota;
	},
	getSettings( state ) {
		return state.settings;
	},
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

register( store );

export default store;
