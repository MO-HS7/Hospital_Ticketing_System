<template>
  <FontAwesomeIcon
    :icon="resolvedIcon"
    :class="iconClass"
    :style="iconStyle"
    :spin="spin"
    :pulse="pulse"
    :fixed-width="fixedWidth"
    :aria-label="label"
    role="img"
  />
</template>

<script setup lang="ts">
/**
 * Centralized Icon Component - Font Awesome Wrapper
 * 
 * Uses official @fortawesome/vue-fontawesome with semantic naming.
 * 
 * Features:
 * - Official Font Awesome icon library
 * - Consistent sizing across the app
 * - RTL/LTR compatible (Font Awesome handles this)
 * - Theme-aware (inherits text color)
 * - Semantic naming with automatic FA icon mapping
 * - Easy version updates via npm
 */

const props = withDefaults(defineProps<{
  name: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
  variant?: 'solid' | 'regular'
  spin?: boolean
  pulse?: boolean
  fixedWidth?: boolean
  label?: string
}>(), {
  size: 'md',
  variant: 'solid',
  spin: false,
  pulse: false,
  fixedWidth: false,
  label: ''
})

// Size mapping to Tailwind classes
const sizeClasses: Record<string, string> = {
  xs: 'w-3 h-3 text-xs',
  sm: 'w-4 h-4 text-sm',
  md: 'w-5 h-5 text-base',
  lg: 'w-6 h-6 text-lg',
  xl: 'w-8 h-8 text-xl',
  '2xl': 'w-10 h-10 text-2xl'
}

const iconClass = computed(() => [
  sizeClasses[props.size],
  'shrink-0'
])

const iconStyle = computed(() => ({}))

/**
 * Semantic Icon Name to Font Awesome Icon Mapping
 * 
 * Maps user-friendly names to official FA icon names.
 * Format: [prefix, iconName] where prefix is 'fas' (solid) or 'far' (regular)
 */
const iconMap: Record<string, [string, string]> = {
  // Dashboard & Analytics
  'dashboard': ['fas', 'gauge-high'],
  'home': ['fas', 'home'],
  'chart-bar': ['fas', 'chart-bar'],
  'chart-pie': ['fas', 'chart-pie'],
  'chart-line': ['fas', 'chart-line'],
  'analytics': ['fas', 'chart-bar'],
  'statistics': ['fas', 'chart-pie'],

  // Tickets & Documents
  'ticket': ['fas', 'ticket'],
  'clipboard': ['fas', 'clipboard'],
  'clipboard-check': ['fas', 'clipboard-check'],
  'clipboard-list': ['fas', 'clipboard-list'],
  'document': ['fas', 'file'],
  'document-text': ['fas', 'file-lines'],
  'folder': ['fas', 'folder'],
  'folder-open': ['fas', 'folder-open'],

  // Status Indicators
  'check': ['fas', 'check'],
  'check-circle': ['fas', 'circle-check'],
  'x-mark': ['fas', 'xmark'],
  'x-circle': ['fas', 'circle-xmark'],
  'exclamation': ['fas', 'exclamation'],
  'exclamation-circle': ['fas', 'circle-exclamation'],
  'exclamation-triangle': ['fas', 'triangle-exclamation'],
  'warning': ['fas', 'triangle-exclamation'],
  'info': ['fas', 'info'],
  'info-circle': ['fas', 'circle-info'],
  'question': ['fas', 'question'],

  // Time & Calendar
  'clock': ['fas', 'clock'],
  'clock-history': ['fas', 'clock-rotate-left'],
  'calendar': ['fas', 'calendar'],
  'calendar-days': ['fas', 'calendar-days'],
  'hourglass': ['fas', 'hourglass-half'],
  'stopwatch': ['fas', 'stopwatch'],

  // Users & People
  'user': ['fas', 'user'],
  'users': ['fas', 'users'],
  'user-group': ['fas', 'user-group'],
  'user-doctor': ['fas', 'user-doctor'],
  'doctor': ['fas', 'user-doctor'],
  'user-nurse': ['fas', 'user-nurse'],
  'nurse': ['fas', 'user-nurse'],
  'user-gear': ['fas', 'user-gear'],
  'user-injured': ['fas', 'user-injured'],
  'patient': ['fas', 'user-injured'],

  // Buildings & Places
  'building': ['fas', 'building'],
  'hospital': ['fas', 'hospital'],
  'house-medical': ['fas', 'house-medical'],
  'office': ['fas', 'building-columns'],

  // Actions
  'plus': ['fas', 'plus'],
  'plus-circle': ['fas', 'plus-circle'],
  'minus': ['fas', 'minus'],
  'refresh': ['fas', 'arrows-rotate'],
  'rotate': ['fas', 'rotate'],
  'search': ['fas', 'magnifying-glass'],
  'filter': ['fas', 'filter'],
  'edit': ['fas', 'pen-to-square'],
  'pen': ['fas', 'pen'],
  'trash': ['fas', 'trash'],
  'trash-can': ['fas', 'trash-can'],
  'delete': ['fas', 'trash-can'],
  'download': ['fas', 'download'],
  'upload': ['fas', 'upload'],
  'print': ['fas', 'print'],
  'share': ['fas', 'share-nodes'],
  'copy': ['fas', 'copy'],
  'clone': ['fas', 'clone'],

  // Navigation
  'menu': ['fas', 'bars'],
  'chevron-left': ['fas', 'chevron-left'],
  'chevron-right': ['fas', 'chevron-right'],
  'chevron-down': ['fas', 'chevron-down'],
  'chevron-up': ['fas', 'chevron-up'],
  'arrow-left': ['fas', 'arrow-left'],
  'arrow-right': ['fas', 'arrow-right'],
  'arrow-up': ['fas', 'arrow-up'],
  'arrow-down': ['fas', 'arrow-down'],
  'external-link': ['fas', 'arrow-up-right-from-square'],
  'expand': ['fas', 'expand'],
  'compress': ['fas', 'compress'],
  'angles-left': ['fas', 'angles-left'],
  'angles-right': ['fas', 'angles-right'],
  'collapse': ['fas', 'angles-left'],

  // System & Settings
  'cog': ['fas', 'gear'],
  'gear': ['fas', 'gear'],
  'gears': ['fas', 'gears'],
  'settings': ['fas', 'gear'],
  'wrench': ['fas', 'wrench'],
  'tools': ['fas', 'screwdriver-wrench'],
  'maintenance': ['fas', 'screwdriver-wrench'],
  'shield': ['fas', 'shield-halved'],
  'shield-check': ['fas', 'shield-halved'],
  'server': ['fas', 'server'],
  'database': ['fas', 'database'],
  'cloud': ['fas', 'cloud'],
  'storage': ['fas', 'hard-drive'],

  // Communication
  'chat': ['fas', 'comment'],
  'comments': ['fas', 'comments'],
  'message': ['fas', 'message'],
  'bell': ['fas', 'bell'],
  'notification': ['fas', 'bell'],
  'mail': ['fas', 'envelope'],
  'email': ['fas', 'envelope'],
  'phone': ['fas', 'phone'],

  // Security
  'lock': ['fas', 'lock'],
  'unlock': ['fas', 'lock-open'],
  'key': ['fas', 'key'],
  'fingerprint': ['fas', 'fingerprint'],
  'user-shield': ['fas', 'user-shield'],
  'ban': ['fas', 'ban'],

  // View & Display
  'eye': ['fas', 'eye'],
  'eye-off': ['fas', 'eye-slash'],
  'eye-slash': ['fas', 'eye-slash'],
  'list': ['fas', 'list'],
  'table': ['fas', 'table'],
  'grid': ['fas', 'grip'],
  'columns': ['fas', 'table-columns'],
  'sort': ['fas', 'sort'],
  'sort-up': ['fas', 'sort-up'],
  'sort-down': ['fas', 'sort-down'],

  // Auth
  'login': ['fas', 'right-to-bracket'],
  'logout': ['fas', 'right-from-bracket'],
  'sign-in': ['fas', 'right-to-bracket'],
  'sign-out': ['fas', 'right-from-bracket'],

  // Misc
  'bolt': ['fas', 'bolt'],
  'lightning': ['fas', 'bolt'],
  'star': ['fas', 'star'],
  'bookmark': ['fas', 'bookmark'],
  'flag': ['fas', 'flag'],
  'tag': ['fas', 'tag'],
  'tags': ['fas', 'tags'],
  'link': ['fas', 'link'],
  'unlink': ['fas', 'unlink'],
  'paperclip': ['fas', 'paperclip'],
  'attachment': ['fas', 'paperclip'],

  // Medical
  'stethoscope': ['fas', 'stethoscope'],
  'pills': ['fas', 'pills'],
  'syringe': ['fas', 'syringe'],
  'heartbeat': ['fas', 'heart-pulse'],
  'heart-pulse': ['fas', 'heart-pulse'],
  'ambulance': ['fas', 'ambulance'],
  'bed': ['fas', 'bed'],
  'prescription': ['fas', 'file-prescription'],
  'medical-file': ['fas', 'file-medical'],
  'notes-medical': ['fas', 'notes-medical'],
  'briefcase-medical': ['fas', 'briefcase-medical'],
  'kit-medical': ['fas', 'kit-medical'],
  'wheelchair': ['fas', 'wheelchair'],

  // Loading / States
  'spinner': ['fas', 'spinner'],
  'loading': ['fas', 'circle-notch'],
  'circle': ['fas', 'circle'],
  'play': ['fas', 'play'],
  'pause': ['fas', 'pause'],
  'stop': ['fas', 'stop'],

  // Toggles
  'toggle-on': ['fas', 'toggle-on'],
  'toggle-off': ['fas', 'toggle-off'],
  'sun': ['fas', 'sun'],
  'moon': ['fas', 'moon'],
  'language': ['fas', 'language'],
  'globe': ['fas', 'globe'],

  // AI / Bot
  'robot': ['fas', 'robot'],
  'chatbot': ['fas', 'robot'],

  // Finance
  'receipt': ['fas', 'receipt'],
  'credit-card': ['fas', 'credit-card'],
  'money': ['fas', 'money-bill'],
  'dollar': ['fas', 'dollar-sign'],

  // Misc UI
  'ellipsis': ['fas', 'ellipsis'],
  'ellipsis-v': ['fas', 'ellipsis-vertical'],
  'more': ['fas', 'ellipsis'],
  'more-vertical': ['fas', 'ellipsis-vertical'],
  'layer-group': ['fas', 'layer-group'],
  'layers': ['fas', 'layer-group'],
}

// Regular (outline) icon variants
const regularIconMap: Record<string, [string, string]> = {
  'clock': ['far', 'clock'],
  'calendar': ['far', 'calendar'],
  'user': ['far', 'user'],
  'comment': ['far', 'comment'],
  'comments': ['far', 'comments'],
  'message': ['far', 'message'],
  'bell': ['far', 'bell'],
  'envelope': ['far', 'envelope'],
  'eye': ['far', 'eye'],
  'eye-slash': ['far', 'eye-slash'],
  'file': ['far', 'file'],
  'file-lines': ['far', 'file-lines'],
  'folder': ['far', 'folder'],
  'folder-open': ['far', 'folder-open'],
  'bookmark': ['far', 'bookmark'],
  'flag': ['far', 'flag'],
  'image': ['far', 'image'],
  'copy': ['far', 'copy'],
  'clone': ['far', 'clone'],
  'check-circle': ['far', 'circle-check'],
  'x-circle': ['far', 'circle-xmark'],
  'trash-can': ['far', 'trash-can'],
  'edit': ['far', 'pen-to-square'],
  'clipboard': ['far', 'clipboard'],
  'heart': ['far', 'heart'],
  'star': ['far', 'star'],
  'hourglass': ['far', 'hourglass'],
}

const resolvedIcon = computed(() => {
  // If variant is regular and regular version exists, use it
  if (props.variant === 'regular' && regularIconMap[props.name]) {
    return regularIconMap[props.name]
  }
  
  // Use solid icon (default)
  if (iconMap[props.name]) {
    return iconMap[props.name]
  }
  
  // Fallback: try to use the name directly as FA icon name
  return ['fas', props.name]
})
</script>
