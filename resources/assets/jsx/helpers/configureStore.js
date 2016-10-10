import thunk from 'redux-thunk'
//import throttle from 'lodash/throttle'
import { browserHistory } from 'react-router'
import { createStore, applyMiddleware, compose } from 'redux'
import { routerMiddleware } from 'react-router-redux'


import api from '../middleware/api'
import adminApp from '../reducers/adminApp'
//import { loadState, saveState } from './localStorage'


const configureStore = () => {
	//const persistedState = undefined //loadState()

	const middlewares = [
		routerMiddleware(browserHistory),
		thunk,
		api
	]

	const store = createStore(
		adminApp,
		compose(
			applyMiddleware(...middlewares),
			window.devToolsExtension ? window.devToolsExtension() : f => f
		)
	)

	// store.subscribe(throttle(() => {
	// 	saveState(store.getState())
	// }, 3000))

	return store
}

// exporting configureStore instead of store
// helps later in tests as you can create
// as many store instances as you want
export default configureStore



