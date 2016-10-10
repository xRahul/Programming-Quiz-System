import {
	CHANGE_QUIZ_FORM_INPUT
} from './constants'

export function changeQuizFormInput(keyValue) {
	return {
		type: CHANGE_QUIZ_FORM_INPUT,
		key: keyValue[0],
		value: keyValue[1]
	}
}