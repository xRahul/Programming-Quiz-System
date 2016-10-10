import React, {Component, PropTypes} from 'react'
import { connect } from 'react-redux'
import QuizViewHeader from '../../components/Quiz/QuizViewHeader'
import QuizViewList from '../../components/Quiz/QuizViewList'
import {getQuizList, deleteQuizBySlug} from '../../actions/allActions'

class QuizView extends Component {

	componentDidMount() {
		this.props.getQuizzes()
	}

	render () {

		const { fetchingQuizList, errorFetchingQuizList,
						quizListData, deleteQuiz, getQuizzes } = this.props


		return (
			<div className='container'>
				<QuizViewHeader
				/>
				<QuizViewList
					fetchingQuizList={fetchingQuizList}
					errorFetchingQuizList={errorFetchingQuizList}
					quizListData={quizListData}
					deleteQuiz={deleteQuiz}
					getQuizzes={getQuizzes}
				/>
			</div>
		)
	}
}

function mapStateToProps(state) {

	const { quiz } = state
	const { quizList } = quiz
	const { fetchingQuizList, errorFetchingQuizList, quizListData } = quizList

	return {
		fetchingQuizList,
		errorFetchingQuizList,
		quizListData
	}
}

function mapDispatchToProps(dispatch) {
	return {
		getQuizzes: (page) => {
			dispatch(getQuizList(page))
		},
		deleteQuiz: (quizSlug) => {
			dispatch(deleteQuizBySlug(quizSlug))
		}
	}
}

QuizView.propTypes = {
	getQuizzes: PropTypes.func.isRequired,
	fetchingQuizList: PropTypes.bool.isRequired,
	errorFetchingQuizList: PropTypes.string.isRequired,
	quizListData: PropTypes.object.isRequired,
	deleteQuiz: PropTypes.func.isRequired
}

export default connect(mapStateToProps, mapDispatchToProps)(QuizView)