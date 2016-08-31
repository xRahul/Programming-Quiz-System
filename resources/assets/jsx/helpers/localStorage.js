export const loadState = () => {
	// necessary when user's privacy settings
	// don't allow local storage
	try {
		const serializedState = localStorage.getItem('state')
		if (serializedState === null) {
			// to let reducers initialize state
			return undefined
		}
		return JSON.parse(serializedState)
	} catch (err) {
		return undefined
	}
}

export const saveState = (state) => {
	try {
		const serializedState = JSON.stringify(state)
		localStorage.setItem('state', serializedState)
	} catch (err) {
		// ignore err to prevent app from crashing
	}
}