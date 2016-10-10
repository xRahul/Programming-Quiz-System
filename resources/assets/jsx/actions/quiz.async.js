import { CALL_API } from '../middleware/api'
import {
	GET_QUIZ_LIST_REQUEST, GET_QUIZ_LIST_SUCCESS, GET_QUIZ_LIST_FAILURE,
	DELETE_QUIZ_REQUEST, DELETE_QUIZ_SUCCESS, DELETE_QUIZ_FAILURE
} from './constants'

export function getQuizList(page = 1) {
	return {
		[CALL_API]: {
			endpoint: 'quiz?page='+parseInt(page),
			method: 'GET',
			types: [GET_QUIZ_LIST_REQUEST, GET_QUIZ_LIST_SUCCESS, GET_QUIZ_LIST_FAILURE]
		}
	}
}

export function deleteQuizBySlug(quizSlug) {
	return {
		[CALL_API]: {
			endpoint: 'quiz/'+quizSlug,
			method: 'DELETE',
			types: [DELETE_QUIZ_REQUEST, DELETE_QUIZ_SUCCESS, DELETE_QUIZ_FAILURE]
		}
	}
}