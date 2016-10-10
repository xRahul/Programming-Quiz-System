// import React, { PropTypes } from 'react'
import React from 'react'
import { Link } from 'react-router'
import {
	PATH_QUIZ_CREATE
} from '../../routing/pathConstants'


const QuizViewHeader = () => {
	return (
		<div className='row'>
			<Link to={PATH_QUIZ_CREATE}
						className='btn btn-primary'>
				Create Quiz
			</Link>
		</div>
	)
}

// App.propTypes = {
// 	children: PropTypes.object.isRequired
// }

export default QuizViewHeader