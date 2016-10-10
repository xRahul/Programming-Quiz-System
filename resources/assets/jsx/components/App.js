import React, { PropTypes } from 'react'

// import { connect } from 'react-redux'

import NavigationTabs from '../containers/NavigationTabs'

const App = ({ children }) => {
	return (
		<div>
			<NavigationTabs />
			{children}
		</div>
	)
}

App.propTypes = {
	children: PropTypes.object.isRequired
}

export default App