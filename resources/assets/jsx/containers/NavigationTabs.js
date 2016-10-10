import $ from 'jquery'
import { Link } from 'react-router'
import { connect } from 'react-redux'
import React, {Component} from 'react'

import {
	PATH_QUIZ_VIEW, PATH_QUESTION_LIST
} from '../routing/pathConstants'

// import { tab } from 'bootstrap-sass'

class NavigationTabs extends Component {

	componentDidMount() {
		$('.nav-tabs li a').click(function () {
			$(this).parent().siblings().removeClass('active')
			$(this).parent().addClass('active')
		})
		$('.nav-tabs li .active').parent().addClass('active')
	}

	render () {
		return (
			<div>
				<ul className='nav nav-tabs' role='tablist' id='navTabs'>
					<li role='presentation'>
						<Link to={PATH_QUIZ_VIEW} activeClassName='active'
									role='tab' data-target='#navTabs'>
							Manage Quizzes
						</Link>
					</li>
					<li role='presentation'>
						<Link to={PATH_QUESTION_LIST} activeClassName='active'
									role='tab' data-target='#navTabs'>
							Manage Questions
						</Link>
					</li>
					<li role='presentation'>
						<Link to='/admin/results' activeClassName='active'
									role='tab' data-target='#navTabs'>
							View Results
						</Link>
					</li>
					<li role='presentation'>
						<Link to='/admin/settings' activeClassName='active'
									role='tab' data-target='#navTabs'>
							Settings
						</Link>
					</li>
				</ul>
			</div>
		)
	}
}



// function mapStateToProps(state) {

// 	const { navigationTabs } = state
// 	const { tabName } = navigationTabs

// 	return {
// 		tabName
// 	}
// }

// export default connect(mapStateToProps)(NavigationTabs)
export default connect()(NavigationTabs)