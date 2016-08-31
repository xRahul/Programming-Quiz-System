import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'

let App = ({ name }) => {
  return (
    <div>{name}</div>
  )
}

function mapStateToProps(state) {
  
  const { demo } = state
  const { name } = demo
  
  return {
    name
  }
}

export default connect(mapStateToProps)(App)