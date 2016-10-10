import React, { PropTypes } from 'react'
import { Field, reduxForm } from 'redux-form'


let QuizForm = ({ handleSubmit }) => {
	return (
		<form onSubmit={handleSubmit}>
			<div className='form-group'>
				<label htmlFor='quiz_name'>Quiz Name</label>
				<Field
					component='input' type='text' name='quiz_name'
					className='form-control' id='quiz_name'
					placeholder='Enter the name of the quiz' />
			</div>
			<div className='form-group'>
				<label htmlFor='quiz_description'>Quiz Description</label>
				<Field
					component='textarea' name='quiz_description'
					className='form-control' id='quiz_description'
					placeholder='Enter the description of the quiz in markdown' />
			</div>
		</form>
	)
}

QuizForm.propTypes = {
	handleSubmit: PropTypes.func.isRequired
}

QuizForm = reduxForm({
	form: 'quiz' // a unique name for this form
})(QuizForm)

export default QuizForm