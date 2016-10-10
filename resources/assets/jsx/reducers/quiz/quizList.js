import {
	GET_QUIZ_LIST_REQUEST, GET_QUIZ_LIST_SUCCESS, GET_QUIZ_LIST_FAILURE
} from '../../actions/constants'

const initialQuizListState = {
	fetchingQuizList: false,
	errorFetchingQuizList: '',
	quizListData: {},
}


function quizList(quizListState = initialQuizListState, action) {
	switch(action.type) {
	case GET_QUIZ_LIST_REQUEST:
		return Object.assign({}, quizListState, {
			fetchingQuizList: true
		})
	case GET_QUIZ_LIST_SUCCESS:
		return Object.assign({}, quizListState, {
			fetchingQuizList: false,
			quizListData: action.response
		})
	case GET_QUIZ_LIST_FAILURE:
		return Object.assign({}, quizListState, {
			fetchingQuizList: false,
			errorFetchingQuizList: action.error
		})
	default:
		return quizListState
	}
}

export default quizList