import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface SharedAuthProps {
  user?: {
    id: number
    name: string
    email: string
  } | null
  permissions?: string[]
  roles?: string[]
}

interface SharedPageProps extends Record<string, unknown> {
  auth?: SharedAuthProps
}

const normalize = (value?: string | null): string | null => {
  if (typeof value !== 'string') {
    return null
  }

  const trimmed = value.trim()

  return trimmed.length > 0 ? trimmed : null
}

export const usePermissions = () => {
  const page = usePage<SharedPageProps>()

  const permissions = computed<string[]>(() => {
    const list = page.props.auth?.permissions

    if (!Array.isArray(list)) {
      return []
    }

    return [...new Set(list.map(normalize).filter((item): item is string => item !== null))]
  })

  const roles = computed<string[]>(() => {
    const list = page.props.auth?.roles

    if (!Array.isArray(list)) {
      return []
    }

    return [...new Set(list.map(normalize).filter((item): item is string => item !== null))]
  })

  const hasPermission = (permission?: string | null): boolean => {
    const normalized = normalize(permission)

    if (!normalized) {
      return false
    }

    return permissions.value.includes(normalized)
  }

  const hasAnyPermission = (permissionList: Array<string | null | undefined>): boolean => {
    if (!Array.isArray(permissionList) || permissionList.length === 0) {
      return false
    }

    return permissionList.some((permission) => hasPermission(permission))
  }

  const hasAllPermissions = (permissionList: Array<string | null | undefined>): boolean => {
    if (!Array.isArray(permissionList) || permissionList.length === 0) {
      return false
    }

    return permissionList.every((permission) => hasPermission(permission))
  }

  const hasRole = (role?: string | null): boolean => {
    const normalized = normalize(role)

    if (!normalized) {
      return false
    }

    return roles.value.includes(normalized)
  }

  const hasAnyRole = (roleList: Array<string | null | undefined>): boolean => {
    if (!Array.isArray(roleList) || roleList.length === 0) {
      return false
    }

    return roleList.some((role) => hasRole(role))
  }

  const can = (permission: string | string[]): boolean => {
    if (Array.isArray(permission)) {
      return hasAllPermissions(permission)
    }

    return hasPermission(permission)
  }

  const cannot = (permission: string | string[]): boolean => !can(permission)

  return {
    permissions,
    roles,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    can,
    cannot,
  }
}
