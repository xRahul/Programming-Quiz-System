import React, { PropTypes } from 'react'

// import { connect } from 'react-redux'

const Paginate = ({
	current_page, last_page, getPage
}) => {

	const pagesBeforeCurrent = Array.from(
		Array(current_page-1), (x,i)=>i+1
	).map((index) => {
		return (
			<li key={index}>
				<a onClick={() => getPage(index)} >
					<span aria-hidden='true'>{index}</span>
				</a>
			</li>
		)
	})
	const pagesAfterCurrent = Array.from(
		Array(last_page-current_page),(x,i)=>i+1+current_page
	).map((index) => {
		return (
			<li key={index}>
				<a onClick={() => getPage(index)} >
					<span aria-hidden='true'>{index}</span>
				</a>
			</li>
		)
	})


	return (
		<div className='col-xs-12' aria-label='Page navigation'>
			<ul className='pagination'>
				<li className={current_page == 1 ? 'disabled' : ''}>
					{current_page != 1 &&
						<a aria-label='Previous'
								onClick={() => getPage(current_page-1)} >
							<span aria-hidden='true'>&laquo;</span>
						</a>
					}
					{current_page == 1 &&
						<span aria-label='Previous' aria-hidden='true'>&laquo;</span>
					}
				</li>
				{pagesBeforeCurrent}
				<li className='active'>
					<a aria-label='Current'
							onClick={() => getPage(current_page)} >
						<span aria-hidden='true'>{current_page}</span>
					</a>
				</li>
				{pagesAfterCurrent}
				<li className={current_page == last_page ? 'disabled' : ''}>
					{current_page != last_page &&
						<a aria-label='Next'
								onClick={() => getPage(current_page+1)} >
							<span aria-hidden='true'>&raquo;</span>
						</a>
					}
					{current_page == last_page &&
						<span aria-label='Next' aria-hidden='true'>&raquo;</span>
					}
				</li>
			</ul>
		</div>
	)
}

Paginate.propTypes = {
	current_page: PropTypes.number.isRequired,
	last_page: PropTypes.number.isRequired,
	getPage: PropTypes.func
}

export default Paginate