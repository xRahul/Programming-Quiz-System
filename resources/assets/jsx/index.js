import 'babel-polyfill'
import React from 'react'
import { render } from 'react-dom'

import Root from './components/Root'
import configureStore from './helpers/configureStore'

// const createStoreWithMiddleware = applyMiddleware(thunk, api)(createStore)

// const store = createStoreWithMiddleware(
// 	adminApp, 
// 	window.devToolsExtension ? window.devToolsExtension() : f => f
// )

const store = configureStore()

render(
  <Root store={store} />,
  document.getElementById('root')
)