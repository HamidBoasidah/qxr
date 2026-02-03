import { ref, readonly } from 'vue'

/**
 * @typedef {Object} NotificationItem
 * @property {number} id
 * @property {string} message
 * @property {('success'|'error'|'warning'|'info')} type
 */

// Singleton state shared across the app
const notifications = ref(/** @type {NotificationItem[]} */([]))

const DEFAULT_DURATION = 4000

/**
 * Global notifications composable (singleton state)
 */
export function useNotifications() {
	/**
	 * Add a notification
	 * @param {{ message: string; type?: 'success'|'error'|'warning'|'info'; duration?: number }} payload
	 * @returns {number} id
	 */
	const addNotification = ({ message, type = 'info', duration = DEFAULT_DURATION } = {}) => {
		const id = Date.now() + Math.random()
		notifications.value.push({ id, message, type })
		if (duration && duration > 0) setTimeout(() => removeNotification(id), duration)
		return id
	}

	/**
	 * Remove a notification by id
	 * @param {number} id
	 */
	const removeNotification = (id) => {
		const idx = notifications.value.findIndex(n => n.id === id)
		if (idx !== -1) notifications.value.splice(idx, 1)
	}

	// Convenience helpers
	const success = (message, duration = DEFAULT_DURATION) => addNotification({ message, type: 'success', duration })
	const error = (message, duration = DEFAULT_DURATION) => addNotification({ message, type: 'error', duration })
	const warning = (message, duration = DEFAULT_DURATION) => addNotification({ message, type: 'warning', duration })
	const info = (message, duration = DEFAULT_DURATION) => addNotification({ message, type: 'info', duration })

	return {
		notifications: readonly(notifications),
		addNotification,
		removeNotification,
		success,
		error,
		warning,
		info,
	}
}

