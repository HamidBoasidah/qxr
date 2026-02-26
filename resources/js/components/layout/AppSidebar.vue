<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 h-screen z-99999 bg-white dark:bg-gray-900 text-gray-900 border-gray-200 dark:border-gray-800',
      // desktop side border على جانب inline-start
      'lg:border-r rtl:lg:border-l rtl:lg:border-r-0',
      // عرض الشريط
      'w-[290px]',
      (isExpanded || isHovered) ? 'lg:w-[290px]' : 'lg:w-[90px]',
    ]"
  :style="sidebarInlineStyle"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div :class="['py-8 flex', (!isExpanded && !isHovered) ? 'lg:justify-center' : 'justify-start']">
      <Link href="/">
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="dark:hidden"
          src="/images/logo/logo.png"
          alt="Logo"
          width="150"
          height="40"
        />
        <img
          v-if="isExpanded || isHovered || isMobileOpen"
          class="hidden dark:block"
          src="/images/logo/logo-dark.png"
          alt="Logo"
          width="150"
          height="40"
        />
        <img
          v-else
          src="/images/logo/logo-icon.png"
          alt="Logo"
          width="32"
          height="32"
        />
      </Link>
    </div>
    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2
              :class="[
                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                !isExpanded && !isHovered
                  ? 'lg:justify-center'
                  : 'justify-start',
              ]"
            >
              <template v-if="isExpanded || isHovered || isMobileOpen">
                {{ menuGroup.title }}
              </template>
              <HorizontalDots v-else />
            </h2>
            <ul class="flex flex-col gap-4">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button
                  v-if="item.subItems"
                  @click="toggleSubmenu(groupIndex, index)"
                  :class="[
                    'menu-item group w-full',
                    {
                      'menu-item-active': isSubmenuOpen(groupIndex, index),
                      'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                    },
                    !isExpanded && !isHovered
                      ? 'lg:justify-center'
                      : 'lg:justify-start',
                  ]"
                >
                  <span
                    :class="[
                      isSubmenuOpen(groupIndex, index)
                        ? 'menu-item-icon-active'
                        : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="menu-item-text"
                    >{{ item.name }}</span
                  >
                  <ChevronDownIcon
                    v-if="isExpanded || isHovered || isMobileOpen"
                    :class="[
                      'ml-auto w-5 h-5 transition-transform duration-200',
                      {
                        'rotate-180 text-brand-500': isSubmenuOpen(
                          groupIndex,
                          index
                        ),
                      },
                    ]"
                  />
                </button>
                <Link
                  v-else-if="item.path"
                  :href="item.path"
                  :class="[
                    'menu-item group',
                    {
                      'menu-item-active': isActive(item.path),
                      'menu-item-inactive': !isActive(item.path),
                    },
                  ]"
                >
                  <span
                    :class="[
                      isActive(item.path)
                        ? 'menu-item-icon-active'
                        : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="menu-item-text"
                    >{{ item.name }}</span
                  >
                </Link>
                <transition
                  @enter="startTransition"
                  @after-enter="endTransition"
                  @before-leave="startTransition"
                  @after-leave="endTransition"
                >
                  <div
                    v-show="
                      isSubmenuOpen(groupIndex, index) &&
                      (isExpanded || isHovered || isMobileOpen)
                    "
                  >
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <Link
                          :href="subItem.path"
                          :class="[
                            'menu-dropdown-item',
                            {
                              'menu-dropdown-item-active': isActive(
                                subItem.path
                              ),
                              'menu-dropdown-item-inactive': !isActive(
                                subItem.path
                              ),
                            },
                          ]"
                        >
                          {{ subItem.name }}
                          <span class="flex items-center gap-1 ml-auto">
                            <span
                              v-if="subItem.new"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              {{ t('common.new') }}
                            </span>
                            <span
                              v-if="subItem.pro"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              {{ t('common.pro') }}
                            </span>
                          </span>
                        </Link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  </aside>
</template>

<script setup>
import { computed } from "vue";
import { useI18n } from 'vue-i18n'
import { Link, usePage } from '@inertiajs/vue3'
import {
  GridIcon,
  UserCircleIcon,
  ChevronDownIcon,
  HorizontalDots,
  BuildingIcon,
  HistoryIcon,
  AddressIcon,
  TagIcon,
  ProductIcon,
  OfferIcon,
  OrderIcon,
  InvoiceIcon,
  ReportIcon,
} from "../../icons"
import { useSidebar } from "@/composables/useSidebar"
import { usePermissions } from "@/composables/usePermissions"

const { t } = useI18n()
const page = usePage()
const { isExpanded, isMobileOpen, isHovered, openSubmenu, isMobile } = useSidebar()
const { can } = usePermissions()

const passes = (permission) => !permission || can(permission)

const filterMenuItems = (items = []) =>
  items
    .map((item) => {
      if (item.subItems) {
        const subItems = filterMenuItems(item.subItems)
        return subItems.length
          ? {
              ...item,
              subItems,
            }
          : null
      }

      return passes(item.permission) ? item : null
    })
    .filter(Boolean)

const filterMenuGroups = (groups = []) =>
  groups
    .map((group) => {
      const items = filterMenuItems(group.items)
      return items.length
        ? {
            ...group,
            items,
          }
        : null
    })
    .filter(Boolean)

const sidebarInlineStyle = computed(() => {
  const desktop = !isMobile.value
  return {
    insetInlineStart: desktop ? '0' : (isMobileOpen.value ? '0' : '-100%'),
    transition: 'inset-inline-start 300ms ease-in-out',
  }
})

const menuGroups = computed(() =>
  filterMenuGroups([
    {
      title: t('menu.menu'),
      items: [
        {
          icon: GridIcon,
          name: t('menu.dashboard'),
          subItems: [
            {
              name: t('menu.dashboard'),
              path: route('admin.dashboard'),
              permission: 'dashboard.view',
            },
          ],
        },
        {
          icon: BuildingIcon,
          name: t('menu.locations'),
          subItems: [
            {
              name: t('menu.governorates'),
              path: route('admin.governorates.index'),
              permission: 'governorates.view',
            },
            {
              name: t('menu.districts'),
              path: route('admin.districts.index'),
              permission: 'districts.view',
            },
            {
              name: t('menu.areas'),
              path: route('admin.areas.index'),
              permission: 'areas.view',
            }
          ],
        },
        {
          icon: GridIcon,
          name: t('menu.categories'),
          path: route('admin.categories.index'),
          permission: 'categories.view',
        },
        {
          icon: TagIcon,
          name: t('menu.tags'),
          path: route('admin.tags.index'),
          permission: 'tags.view',
        },
        {
          icon: ProductIcon,
          name: t('menu.products'),
          path: route('admin.products.index'),
          permission: 'products.view',
        },
        {
          icon: OfferIcon,
          name: t('menu.offers'),
          path: route('admin.offers.index'),
          permission: 'offers.view',
        },
        {
          icon: InvoiceIcon,
          name: t('menu.invoices'),
          path: route('admin.invoices.index'),
          permission: 'invoices.view',
        },
        {
          icon: OrderIcon,
          name: t('menu.orders'),
          path: route('admin.orders.index'),
          permission: 'orders.view',
        },
        {
          icon: ReportIcon,
          name: t('reports.title'),
          path: route('admin.reports.index'),
          permission: 'reports.view',
        },
        {
          icon: UserCircleIcon,
          name: t('menu.profile'),
          path: route('admin.profile'),
          permission: 'profile.view',
        },
        {
          icon: UserCircleIcon,
          name: t('menu.users'),
          path: route('admin.users.index'),
          permission: 'users.view',
        },
        {
          icon: AddressIcon,
          name: t('menu.addresses'),
          path: route('admin.addresses.index'),
          permission: 'addresses.view',
        },
        {
          icon: UserCircleIcon,
          name: t('menu.admins'),
          subItems: [
            {
              name: t('menu.admins'),
              path: route('admin.admins.index'),
              permission: 'admins.view',
            },
            {
              name: t('menu.roles'),
              path: route('admin.roles.index'),
              permission: 'roles.view',
            },
            {
              name: t('menu.permissions'),
              path: route('admin.permissions.index'),
              permission: 'permissions.view',
            },
          ],
        },
        {
          icon: HistoryIcon,
          name: t('menu.activityLogs'),
          path: route('admin.activitylogs.index'),
          permission: 'activitylogs.view',
        },
      ],
    },
  ]),
)

const isActive = (path) => page.url === path;

const toggleSubmenu = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`;
  openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.value.some((group) =>
    group.items.some(
      (item) =>
        item.subItems && item.subItems.some((subItem) => isActive(subItem.path))
    )
  )
})

const isSubmenuOpen = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`;
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups.value[groupIndex].items[itemIndex].subItems?.some((subItem) =>
        isActive(subItem.path)
      ))
  )
};

const startTransition = (el) => {
  el.style.height = "auto";
  const height = el.scrollHeight;
  el.style.height = "0px";
  el.offsetHeight; // force reflow
  el.style.height = height + "px";
};

const endTransition = (el) => {
  el.style.height = "";
};
</script>
