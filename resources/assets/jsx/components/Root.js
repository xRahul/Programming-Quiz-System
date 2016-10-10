import React, { PropTypes } from 'react'
import { Provider } from 'react-redux'
import { Router, browserHistory } from 'react-router'
import { syncHistoryWithStore } from 'react-router-redux'
import routes from '../routing/routes'

const Root = ({ store }) => {

	const history = syncHistoryWithStore(browserHistory, store)

	return (
		<Provider store={store}>
			<Router routes={routes} history={history} />
		</Provider>
	)
}

Root.propTypes = {
	store: PropTypes.object.isRequired
}

export default Root