import { combineReducers } from 'redux'
import { reducer as form } from 'redux-form'
import { routerReducer as routing } from 'react-router-redux'

import quiz from './quiz/index'

const adminApp = combineReducers({
	quiz,
	form,
	routing
})

export default adminApp