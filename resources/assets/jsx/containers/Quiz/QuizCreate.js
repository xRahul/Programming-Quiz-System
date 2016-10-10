import React, {Component, PropTypes} from 'react'
import { connect } from 'react-redux'
import QuizForm from '../../components/Quiz/QuizForm'

class QuizCreate extends Component {

	render () {
		const { onCreateQuizClick } = this.props
		return (
			<QuizForm
				handleSubmit={onCreateQuizClick}
			/>
		)
	}
}

// function mapStateToProps(state) {
// }

function mapDispatchToProps(dispatch) {
	return {
		onCreateQuizClick: () => console.log('create clicked')
	}
}

QuizCreate.propTypes = {
	onCreateQuizClick: PropTypes.func.isRequired
}

export default connect(null, mapDispatchToProps)(QuizCreate)