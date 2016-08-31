import React from 'react'
import { connect } from 'react-redux'
import { Navbar, Nav, NavItem, Input, Button } from 'react-bootstrap';

import { 
  loginUser, logoutUser 
} from '../actions/all'



let NavBar = ({ yo, ui }) => {
  return (
    <div>
    <Navbar staticTop toggleNavKey={0}>
      <Nav pullRight eventKey={0}>
        <div className='navbar-form form-inline'>
          <XXcomponent 
            featureList = {featureList}
            onFeatureChange = { (feature) => dispatch(changeActiveFeature(feature)) }
          />
        </div>
      </Nav>
    </Navbar>
  </div>
  )
}

function mapStateToProps(state) {
  
  const { XY } = state
  const { yo, ui } = XY
  
  return {
    yo, ui
  }
}

export default connect(mapStateToProps)(NavBar)
