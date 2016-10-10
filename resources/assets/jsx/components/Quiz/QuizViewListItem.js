import React, { PropTypes } from 'react'
import { Link } from 'react-router'
import {
	PATH_QUIZ_DETAILS, PATH_QUIZ_EDIT
} from '../../routing/pathConstants'

const QuizViewListItem = ({item, deleteQuiz}) => {

	return (
		<tr>
			<td>{item.id}</td>
			<td>
				<Link to={PATH_QUIZ_DETAILS+'/'+item.slug}>
					{item.name}
				</Link>
			</td>
			<td>{item.active_status?'Active':'Inactive'}</td>
			<td>
				<Link to={PATH_QUIZ_EDIT+'/'+item.slug}
							className='btn btn-warning'>
					Edit
				</Link>
			</td>
			<td>
				<a className='btn btn-danger'
						onClick={() => deleteQuiz(item.slug)}	>
					Delete
				</a>
			</td>
		</tr>
	)
}

QuizViewListItem.propTypes = {
	item: PropTypes.object.isRequired,
	deleteQuiz: PropTypes.func.isRequired
}

export default QuizViewListItem