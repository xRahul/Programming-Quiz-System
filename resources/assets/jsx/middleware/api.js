import 'whatwg-fetch'

export const CALL_API = Symbol('Call API')
export const featureReplacementText = 'replaceThisWithFeatureName'

function callApi(server, endpoint, method, payload) {

  
  const BASE_URL = 'http://laravelquiz.app/'
  
  const token = localStorage.getItem('access_token') || null
  let metadata = {}
  
  if(token) {
    metadata = {
      headers: { 
        'Authorization': `Bearer ${token}`,
        'Content-Type': `application/json`
      },
      method: method != '' ? method : 'GET'
    }
    if(payload != '') {
      if(method == '' || method == 'GET') {
        endpoint = endpoint+'?server='+payload.server
        if('action' in payload) {
          endpoint += '&action='+payload.action
        }
      } else {
        metadata.body = JSON.stringify(payload)
      }
    }
  } else {
    throw "No token saved!"
  }
  
  return fetch(BASE_URL + endpoint, metadata)
}



export default ({ dispatch, getState }) => next => action => {
  
  const callAPI = action[CALL_API]

  // So the middleware doesn't get applied to every single action
  // middleware only applies to actions having [CALL_API]: {}
  if (typeof callAPI === 'undefined') {
    return next(action)
  }
  
  const state = getState()
  const server = state.server.activeServer
  const username = state.auth.username
  const configFile = state.tabs.currentTab

  // getting the data from action
  let { endpoint, types, method='GET', payload={} } = callAPI

  if(payload != '') {
    payload.server = server.replace("bbb", "ccc")
  }
  
  // add feature name
  if(endpoint.indexOf(featureReplacementText) > -1) {
    endpoint = endpoint.replace(featureReplacementText, feature)
  }

  const [ requestType, successType, errorType ] = types
  
  dispatch({type: requestType})

  return callApi(server, endpoint, method, payload).then(
    response => response.json().then(
      data => ({ data, response })
    )
  ).then(
    ({ data, response }) => {
      if (!response.ok || data.code == 0 || 'error' in data) {
        if(data.error == "invalid_grant") {
          dispatch(logoutUser())
        }
        return next({
          error: data,
          type: errorType
        })
      }
      return next({
        response: data.data,
        type: successType
      })
    }
  ).catch(
    error => next({
      error: error,
      type: errorType
    })
  )
}