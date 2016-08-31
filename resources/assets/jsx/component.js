import React, { Component, PropTypes } from 'react'

export default class ComponentName extends Component {
  

  render() {
    const { prop1, prop2 } = this.props    
    return ( 
      <div className="container">     

      </div>
    )
  }
}

ComponentName.propTypes = {
  prop1: PropTypes.func.isRequired,
  prop2: PropTypes.bool.isRequired
}