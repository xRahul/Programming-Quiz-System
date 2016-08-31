import thunk from 'redux-thunk'
import throttle from 'lodash/throttle'
import { createStore, applyMiddleware, compose } from 'redux'

import api from '../middleware/api'
import adminApp from '../reducers/adminApp'
import { loadState, saveState } from './localStorage'


const configureStore = () => {
	const persistedState = loadState()
	const store = createStore(
		adminApp,
		persistedState,
		compose(
			applyMiddleware(thunk, api),
			window.devToolsExtension ? window.devToolsExtension() : f => f
		)
	)
	store.subscribe(throttle(() => {
		saveState(store.getState())
	}, 3000))
	return store
}

// exporting configureStore instead of store
// helps later in tests as you can create
// as many store instances as you want
export default configureStore



