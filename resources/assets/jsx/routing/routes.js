import React from 'react'
import { Route, IndexRoute } from 'react-router'
import App from '../components/App'
import Home from '../components/Home'
import QuizView from '../containers/Quiz/QuizView'
import QuizCreate from '../containers/Quiz/QuizCreate'
import QuestionList from '../components/Question/QuestionList'

import {
	PATH_ADMIN_HOME, PATH_QUIZ_VIEW, PATH_QUESTION_LIST,
	PATH_QUIZ_CREATE
} from './pathConstants'

const routes = (
	<Route path={PATH_ADMIN_HOME} component={App} >
		<IndexRoute component={Home} />
		<Route path={PATH_QUIZ_VIEW} component={QuizView} />
		<Route path={PATH_QUIZ_CREATE} component={QuizCreate} />
		<Route path={PATH_QUESTION_LIST} component={QuestionList} />
	</Route>
)

export default routes