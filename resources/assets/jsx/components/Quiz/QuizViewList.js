import React, { PropTypes } from 'react'
import QuizViewListItem from './QuizViewListItem'
import Paginate from '../Paginate'

const QuizViewList = ({
	fetchingQuizList, quizListData, errorFetchingQuizList,
	deleteQuiz, getQuizzes
}) => {

	const quizListItems = quizListData.data ? quizListData.data.map(
		(elem, index) => {
			return (
				<QuizViewListItem key={index} item={elem} deleteQuiz={deleteQuiz} />
			)
		}
	) : null

	return (
		<div className='table-responsive'>
			{fetchingQuizList &&
				<img className='img-responsive'
					src={'/img/loading_spinner.gif'} />
			}
			{!fetchingQuizList && errorFetchingQuizList != '' &&
				<div className='text-center'>{errorFetchingQuizList}</div>
			}
			{!fetchingQuizList && errorFetchingQuizList == ''
				&& quizListItems &&
				<table className='table table-hover'>
					<tbody>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>Status</th>
							<th>Edit</th>
							<th>Delete</th>
						</tr>
						{quizListItems}
						<tr className='text-center'>
							<td colSpan={5}>
								<Paginate
									current_page={quizListData.current_page}
									last_page={quizListData.last_page}
									getPage={getQuizzes} />
							</td>
						</tr>
					</tbody>
				</table>
			}
		</div>
	)
}

QuizViewList.propTypes = {
	fetchingQuizList: PropTypes.bool.isRequired,
	errorFetchingQuizList: PropTypes.string.isRequired,
	quizListData: PropTypes.object.isRequired,
	deleteQuiz: PropTypes.func.isRequired,
	getQuizzes: PropTypes.func.isRequired
}

export default QuizViewList