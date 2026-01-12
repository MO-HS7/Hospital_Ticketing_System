<template>
  <NuxtLayout name="admin">
    <div class="space-y-4 sm:space-y-6 lg:space-y-8">
      <!-- Header: Date + SLA Pressure Bar -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <!-- Left: Date + Refresh -->
        <div class="flex items-center gap-2 sm:gap-3">
          <span class="text-xs sm:text-sm text-[var(--color-text-muted)]">{{ currentDate }}</span>
          <button @click="refreshAll" class="btn-ghost p-1.5 sm:p-2 rounded-full hover:bg-[var(--color-bg-tertiary)] touch-target-sm" :disabled="loading">
            <Icon name="refresh" size="sm" :spin="loading" />
          </button>
        </div>
        
        <!-- Right: SLA Pressure Indicator (Segmented Bar) -->
        <NuxtLink 
          to="/admin/sla-monitor"
          class="group flex items-center gap-3 px-4 py-2 rounded-xl bg-[var(--color-bg-secondary)] hover:bg-[var(--color-bg-tertiary)] border border-[var(--color-border)] transition-all cursor-pointer"
        >
          <span class="text-xs font-medium text-[var(--color-text-muted)]">SLA</span>
          
          <!-- Segmented Pressure Bar -->
          <div class="flex items-center h-5 sm:h-6 rounded-lg overflow-hidden bg-[var(--color-bg-tertiary)] min-w-[100px] sm:min-w-[140px] flex-1 sm:flex-initial max-w-[200px] sm:max-w-none">
            <!-- On Track Segment -->
            <div 
              class="h-full flex items-center justify-center px-2 bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-500 relative group/seg"
              :style="{ width: `${slaOnTrackPercent}%`, minWidth: slaOnTrack > 0 ? '30px' : '0' }"
              :title="`${$t('commandCenter.onTrack')}: ${slaOnTrack}`"
            >
              <span v-if="slaOnTrack > 0" class="text-[10px] font-bold text-white">{{ slaOnTrack }}</span>
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-emerald-600 text-white text-[10px] rounded opacity-0 group-hover/seg:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none">
                {{ $t('commandCenter.onTrack') }}: {{ slaOnTrack }} {{ $t('nav.tickets') }}
              </div>
            </div>
            
            <!-- Warning Segment -->
            <div 
              class="h-full flex items-center justify-center px-2 bg-gradient-to-r from-amber-400 to-amber-500 transition-all duration-500 relative group/seg"
              :style="{ width: `${slaWarningPercent}%`, minWidth: slaWarning > 0 ? '30px' : '0' }"
              :class="{ 'animate-pulse': slaWarning > 0 }"
            >
              <span v-if="slaWarning > 0" class="text-[10px] font-bold text-white">{{ slaWarning }}</span>
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-amber-600 text-white text-[10px] rounded opacity-0 group-hover/seg:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none">
                {{ $t('commandCenter.slaWarningShort') }}: {{ slaWarning }} {{ $t('nav.tickets') }}
              </div>
            </div>
            
            <!-- Critical Segment -->
            <div 
              class="h-full flex items-center justify-center px-2 bg-gradient-to-r from-red-500 to-red-600 transition-all duration-500 relative group/seg"
              :style="{ width: `${slaCriticalPercent}%`, minWidth: slaCritical > 0 ? '30px' : '0' }"
              :class="{ 'animate-pulse': slaCritical > 0 }"
            >
              <span v-if="slaCritical > 0" class="text-[10px] font-bold text-white">{{ slaCritical }}</span>
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-red-600 text-white text-[10px] rounded opacity-0 group-hover/seg:opacity-100 transition-opacity whitespace-nowrap z-20 pointer-events-none">
                {{ $t('commandCenter.slaCritical') }}: {{ slaCritical }} {{ $t('nav.tickets') }}
              </div>
            </div>
          </div>
          
          <Icon name="arrow-right" size="xs" class="text-[var(--color-text-muted)] group-hover:translate-x-0.5 transition-transform" />
        </NuxtLink>
      </div>

      <!-- Interactive Circular Stat Cards -->
      <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4 lg:gap-6">
        <!-- Active Doctors -->
        <div 
          class="flex flex-col items-center cursor-pointer group"
          @click="selectStat('doctors')"
        >
          <div 
            class="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 mb-2 sm:mb-3 transition-transform duration-500"
            :class="{ 'scale-110': selectedStat === 'doctors' }"
          >
            <svg class="w-full h-full transition-transform duration-700" :class="{ '-rotate-[360deg]': selectedStat === 'doctors', '-rotate-90': selectedStat !== 'doctors' }" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-border)" stroke-width="6" />
              <circle 
                cx="50" cy="50" r="45" 
                fill="none" 
                stroke="#10b981" 
                stroke-width="6" 
                stroke-linecap="round"
                class="transition-all duration-1000"
                :stroke-dasharray="selectedStat === 'doctors' ? '283 283' : `${(metrics.staff?.doctors || 0) * 2.83} 283`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <Transition name="bounce" mode="out-in">
                <span :key="selectedStat === 'doctors' ? 'selected' : 'normal'" class="text-2xl font-bold text-emerald-600">
                  {{ selectedStat === 'doctors' ? metrics.staff?.doctors || 0 : metrics.staff?.doctors || 0 }}
                </span>
              </Transition>
              <Icon name="user-doctor" size="sm" class="text-emerald-600 mt-0.5 sm:mt-1 transition-transform duration-300 group-hover:scale-110 sm:w-5 sm:h-5" />
            </div>
          </div>
          <p class="text-xs sm:text-sm font-medium text-[var(--color-text-secondary)] transition-colors text-center" :class="{ 'text-emerald-600': selectedStat === 'doctors' }">
            {{ $t('commandCenter.activeDoctors') }}
          </p>
          <!-- Detail popup on select -->
          <Transition name="fade-slide">
            <div v-if="selectedStat === 'doctors'" class="mt-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-medium text-emerald-700 dark:text-emerald-400">
              {{ $t('commandCenter.ticketBreakdown') }}: {{ metrics.departments_stats?.length || 0 }} {{ $t('nav.departments') }}
            </div>
          </Transition>
        </div>

        <!-- Active Patients (Today's Tickets) -->
        <div 
          class="flex flex-col items-center cursor-pointer group"
          @click="selectStat('patients')"
        >
          <div 
            class="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 mb-2 sm:mb-3 transition-transform duration-500"
            :class="{ 'scale-110': selectedStat === 'patients' }"
          >
            <svg class="w-full h-full transition-transform duration-700" :class="{ '-rotate-[360deg]': selectedStat === 'patients', '-rotate-90': selectedStat !== 'patients' }" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-border)" stroke-width="6" />
              <circle 
                cx="50" cy="50" r="45" 
                fill="none" 
                stroke="#10b981" 
                stroke-width="6" 
                stroke-linecap="round"
                class="transition-all duration-1000"
                :stroke-dasharray="selectedStat === 'patients' ? '283 283' : `${Math.min((insights.today?.total || 0) * 2, 283)} 283`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-lg sm:text-xl lg:text-2xl font-bold text-emerald-600">{{ insights.today?.total || 0 }}</span>
              <Icon name="user-group" size="sm" class="text-emerald-600 mt-0.5 sm:mt-1 transition-transform duration-300 group-hover:scale-110 sm:w-5 sm:h-5" />
            </div>
          </div>
          <p class="text-xs sm:text-sm font-medium text-[var(--color-text-secondary)] transition-colors text-center" :class="{ 'text-emerald-600': selectedStat === 'patients' }">
            {{ $t('commandCenter.activePatients') }}
          </p>
          <Transition name="fade-slide">
            <div v-if="selectedStat === 'patients'" class="mt-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-medium text-emerald-700 dark:text-emerald-400">
              {{ insights.today?.completed || 0 }} {{ $t('commandCenter.completed') }} {{ $t('commandCenter.todayTickets') }}
            </div>
          </Transition>
        </div>

        <!-- Maintenance Staff -->
        <div 
          class="flex flex-col items-center cursor-pointer group"
          @click="selectStat('maintenance')"
        >
          <div 
            class="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 mb-2 sm:mb-3 transition-transform duration-500"
            :class="{ 'scale-110': selectedStat === 'maintenance' }"
          >
            <svg class="w-full h-full transition-transform duration-700" :class="{ '-rotate-[360deg]': selectedStat === 'maintenance', '-rotate-90': selectedStat !== 'maintenance' }" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-border)" stroke-width="6" />
              <circle 
                cx="50" cy="50" r="45" 
                fill="none" 
                stroke="#10b981" 
                stroke-width="6" 
                stroke-linecap="round"
                class="transition-all duration-1000"
                :stroke-dasharray="selectedStat === 'maintenance' ? '283 283' : `${(metrics.staff?.maintenance || 0) * 2.83} 283`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-lg sm:text-xl lg:text-2xl font-bold text-emerald-600">{{ metrics.staff?.maintenance || 0 }}</span>
              <Icon name="tools" size="sm" class="text-emerald-600 mt-0.5 sm:mt-1 transition-transform duration-300 group-hover:scale-110 sm:w-5 sm:h-5" />
            </div>
          </div>
          <p class="text-xs sm:text-sm font-medium text-[var(--color-text-secondary)] transition-colors text-center" :class="{ 'text-emerald-600': selectedStat === 'maintenance' }">
            {{ $t('commandCenter.maintenanceStaff') }}
          </p>
          <Transition name="fade-slide">
            <div v-if="selectedStat === 'maintenance'" class="mt-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-medium text-emerald-700 dark:text-emerald-400">
              {{ insights.at_risk?.count || 0 }} {{ $t('commandCenter.ticketsAtRisk') }}
            </div>
          </Transition>
        </div>

        <!-- Reception Staff -->
        <div 
          class="flex flex-col items-center cursor-pointer group"
          @click="selectStat('reception')"
        >
          <div 
            class="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 mb-2 sm:mb-3 transition-transform duration-500"
            :class="{ 'scale-110': selectedStat === 'reception' }"
          >
            <svg class="w-full h-full transition-transform duration-700" :class="{ '-rotate-[360deg]': selectedStat === 'reception', '-rotate-90': selectedStat !== 'reception' }" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-border)" stroke-width="6" />
              <circle 
                cx="50" cy="50" r="45" 
                fill="none" 
                stroke="#10b981" 
                stroke-width="6" 
                stroke-linecap="round"
                class="transition-all duration-1000"
                :stroke-dasharray="selectedStat === 'reception' ? '283 283' : `${(metrics.staff?.reception || 0) * 2.83} 283`"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-lg sm:text-xl lg:text-2xl font-bold text-emerald-600">{{ metrics.staff?.reception || 0 }}</span>
              <Icon name="building" size="sm" class="text-emerald-600 mt-0.5 sm:mt-1 transition-transform duration-300 group-hover:scale-110 sm:w-5 sm:h-5" />
            </div>
          </div>
          <p class="text-xs sm:text-sm font-medium text-[var(--color-text-secondary)] transition-colors text-center" :class="{ 'text-emerald-600': selectedStat === 'reception' }">
            {{ $t('commandCenter.receptionStaff') }}
          </p>
          <Transition name="fade-slide">
            <div v-if="selectedStat === 'reception'" class="mt-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-medium text-emerald-700 dark:text-emerald-400">
              {{ insights.awaiting_payment?.count || 0 }} {{ $t('commandCenter.awaitingPayment') }}
            </div>
          </Transition>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
        <!-- Department Distribution - Interactive Radial -->
        <div class="card p-4 sm:p-5 lg:p-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-3 sm:mb-4">
            <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $t('commandCenter.ticketsByDept') }}</h3>
            <span class="text-xs text-[var(--color-text-muted)]">{{ totalDeptTickets }} {{ $t('nav.tickets') }}</span>
          </div>
          
          <!-- Radial Chart -->
          <div class="relative flex items-center justify-center min-h-[180px] sm:min-h-[200px] lg:min-h-[220px]">
            <svg viewBox="0 0 200 200" class="w-40 h-40 sm:w-44 sm:h-44 md:w-48 md:h-48 lg:w-52 lg:h-52">
              <!-- Background circle -->
              <circle cx="100" cy="100" r="80" fill="none" stroke="var(--color-border)" stroke-width="24" opacity="0.3" />
              
              <!-- Department segments -->
              <circle 
                v-for="(dept, idx) in radialDepartments" 
                :key="dept.id"
                cx="100" cy="100" r="80"
                fill="none"
                :stroke="getDeptColor(idx, dept.pressure)"
                :stroke-width="hoveredDept === dept.id ? 28 : 24"
                :stroke-dasharray="`${dept.arcLength} ${502.65 - dept.arcLength}`"
                :stroke-dashoffset="`${-dept.offset}`"
                stroke-linecap="round"
                class="transition-all duration-300 cursor-pointer"
                :class="{ 'opacity-40': hoveredDept && hoveredDept !== dept.id }"
                style="transform: rotate(-90deg); transform-origin: center;"
                @mouseenter="hoveredDept = dept.id"
                @mouseleave="hoveredDept = null"
                @click="navigateToDeptTickets(dept.id)"
              />
              
              <!-- Center content -->
              <foreignObject x="40" y="40" width="120" height="120">
                <div class="w-full h-full flex flex-col items-center justify-center text-center">
                  <Transition name="fade" mode="out-in">
                    <div v-if="hoveredDept" :key="hoveredDept" class="animate-fade-in">
                      <Icon :name="getDeptIcon(getHoveredDeptData?.slug)" size="lg" class="text-[var(--color-text-muted)] mb-1" />
                      <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ getHoveredDeptData?.count || 0 }}</p>
                      <p class="text-xs text-[var(--color-text-muted)] truncate max-w-[100px]">
                        {{ locale === 'ar' ? getHoveredDeptData?.name_ar : getHoveredDeptData?.name_en }}
                      </p>
                    </div>
                    <div v-else key="default">
                      <p class="text-2xl sm:text-3xl font-bold text-[var(--color-text-primary)]">{{ totalDeptTickets }}</p>
                      <p class="text-xs text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}</p>
                    </div>
                  </Transition>
                </div>
              </foreignObject>
            </svg>
            
            <!-- Hover tooltip - Hidden on mobile, shown on hover for desktop -->
            <Transition name="fade">
              <div 
                v-if="hoveredDept && getHoveredDeptData" 
                class="hidden sm:block absolute top-0 end-0 p-3 sm:p-4 rounded-xl bg-[var(--color-bg-secondary)] border border-[var(--color-border)] shadow-lg z-10 text-center min-w-[100px] sm:min-w-[120px]"
              >
                <Icon 
                  :name="getDeptIcon(getHoveredDeptData?.slug)" 
                  size="xl" 
                  class="text-[var(--color-text-muted)] mb-2" 
                />
                <p class="font-semibold text-[var(--color-text-primary)] mb-1">
                  {{ locale === 'ar' ? getHoveredDeptData?.name_ar : getHoveredDeptData?.name_en }}
                </p>
                <p class="text-lg font-bold text-[var(--color-text-primary)]">
                  {{ getHoveredDeptData?.count || 0 }} {{ $t('nav.tickets') }}
                </p>
                <p class="mt-2 text-[10px] text-primary-600">{{ $t('commandCenter.clickToView') }} →</p>
              </div>
            </Transition>
          </div>
          
          <!-- Legend - Progressive Disclosure for 30+ Departments -->
          <div class="mt-3 sm:mt-4">
            <!-- Top/All Toggle -->
            <div class="flex items-center justify-center gap-1 mb-3">
              <button 
                @click="legendMode = 'top'; legendExpanded = false"
                class="px-2 sm:px-3 py-1 rounded-full text-[10px] font-medium transition-all touch-target-sm"
                :class="legendMode === 'top' 
                  ? 'bg-primary-600 text-white' 
                  : 'bg-[var(--color-bg-tertiary)] text-[var(--color-text-muted)] hover:bg-[var(--color-bg-tertiary)]/80'"
              >
                {{ $t('commandCenter.topDepartments') }}
              </button>
              <button 
                @click="legendMode = 'all'; legendExpanded = true"
                class="px-3 py-1 rounded-full text-[10px] font-medium transition-all"
                :class="legendMode === 'all' 
                  ? 'bg-primary-600 text-white' 
                  : 'bg-[var(--color-bg-tertiary)] text-[var(--color-text-muted)] hover:bg-[var(--color-bg-tertiary)]/80'"
              >
                {{ $t('commandCenter.allDepartments') }}
              </button>
            </div>
            
            <!-- Legend Items -->
            <div class="flex flex-wrap justify-center gap-2">
              <div 
                v-for="dept in visibleLegendDepts" 
                :key="`legend-${dept.id}`"
                class="flex items-center gap-1.5 px-2 py-1 rounded-full text-xs cursor-pointer transition-all"
                :class="hoveredDept === dept.id ? 'bg-[var(--color-bg-tertiary)] scale-105' : 'hover:bg-[var(--color-bg-tertiary)]'"
                @mouseenter="hoveredDept = dept.id"
                @mouseleave="hoveredDept = null"
                @click="navigateToDeptTickets(dept.id)"
              >
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: getDeptColor(radialDepartments.findIndex((d: any) => d.id === dept.id), dept.pressure) }"></span>
                <Icon :name="getDeptIcon(dept.slug)" size="xs" class="text-[var(--color-text-muted)] flex-shrink-0" />
                <span class="text-[var(--color-text-secondary)] truncate max-w-[80px]">
                  {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
                </span>
                <span class="text-[var(--color-text-muted)] font-medium flex-shrink-0">({{ dept.count }})</span>
              </div>
            </div>
            
            <!-- Expand/Collapse Control (only in "Top" mode with hidden departments) -->
            <div v-if="legendMode === 'top' && hiddenDeptCount > 0" class="flex justify-center mt-2">
              <button 
                @click="legendExpanded = !legendExpanded"
                class="px-3 py-1 rounded-full text-[10px] font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all flex items-center gap-1"
              >
                <Icon :name="legendExpanded ? 'chevron-up' : 'chevron-down'" size="xs" />
                {{ legendExpanded ? $t('commandCenter.collapse') : `${$t('commandCenter.showAll')} (+${hiddenDeptCount})` }}
              </button>
            </div>
          </div>
          
          <!-- Empty state -->
          <div v-if="!radialDepartments.length" class="text-center py-8 text-[var(--color-text-muted)]">
            <Icon name="building" size="xl" class="mb-2 opacity-50" />
            <p class="text-sm">{{ $t('commandCenter.noData') }}</p>
          </div>
        </div>

        <!-- Daily Operations Snapshot -->
        <div class="card p-4 sm:p-5 lg:p-6">
          <h3 class="font-semibold text-[var(--color-text-primary)] mb-3 sm:mb-4 flex items-center gap-2 text-sm sm:text-base">
            <Icon name="chart-bar" size="md" class="text-primary-600" />
            {{ $t('commandCenter.dailyOperations') }}
          </h3>
          
          <!-- Pipeline Flow Track - Ticket Lifecycle Stages -->
          <div class="space-y-4">
            <!-- Total Counter -->
            <div class="flex items-center justify-between">
              <span class="text-xs text-[var(--color-text-muted)]">{{ $t('commandCenter.statusDistribution') }}</span>
              <span class="text-xs font-medium text-[var(--color-text-secondary)]">{{ metrics.tickets?.total || 0 }} {{ $t('dashboard.totalTickets') }}</span>
            </div>
            
            <!-- Pipeline Flow -->
            <div class="flex items-end gap-1.5 sm:gap-2">
              <!-- Pending Stage -->
              <div 
                class="flex-1 group cursor-pointer"
                @click="navigateToTickets('pending')"
              >
                <div 
                  class="relative rounded-lg sm:rounded-xl bg-gradient-to-b from-amber-400 to-amber-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-amber-500/20"
                  :style="{ minHeight: `${Math.max(50, pendingPercent * 1.2)}px` }"
                >
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-1.5 sm:p-2">
                    <Icon name="clock" size="sm" class="mb-0.5 sm:mb-1 opacity-90 sm:w-5 sm:h-5" />
                    <span class="text-base sm:text-lg lg:text-xl font-bold">{{ metrics.tickets?.pending || 0 }}</span>
                    <span class="text-[8px] sm:text-[10px] font-medium opacity-80 text-center leading-tight">{{ $t('dashboard.pending') }}</span>
                  </div>
                  <!-- Tooltip - hidden on mobile -->
                  <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-amber-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                    {{ pendingPercent }}% · {{ $t('commandCenter.clickToView') }}
                  </div>
                </div>
                <!-- Connector Arrow - smaller on mobile -->
                <div class="flex justify-center mt-0.5 sm:mt-1">
                  <Icon name="arrow-down" size="xs" class="text-amber-400/50 w-3 h-3 sm:w-4 sm:h-4" />
                </div>
              </div>
              
              <!-- In Progress Stage -->
              <div 
                class="flex-1 group cursor-pointer"
                @click="navigateToTickets('in_progress')"
              >
                <div 
                  class="relative rounded-lg sm:rounded-xl bg-gradient-to-b from-blue-400 to-blue-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-blue-500/20"
                  :style="{ minHeight: `${Math.max(50, inProgressPercent * 1.2)}px` }"
                >
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-1.5 sm:p-2">
                    <Icon name="spinner" size="sm" class="mb-0.5 sm:mb-1 opacity-90 animate-spin sm:w-5 sm:h-5" />
                    <span class="text-base sm:text-lg lg:text-xl font-bold">{{ metrics.tickets?.in_progress || 0 }}</span>
                    <span class="text-[8px] sm:text-[10px] font-medium opacity-80 text-center leading-tight">{{ $t('dashboard.inProgressTickets') }}</span>
                  </div>
                  <!-- Tooltip - hidden on mobile -->
                  <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-blue-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                    {{ inProgressPercent }}% · {{ $t('commandCenter.clickToView') }}
                  </div>
                </div>
                <!-- Connector Arrow -->
                <div class="flex justify-center mt-0.5 sm:mt-1">
                  <Icon name="arrow-down" size="xs" class="text-blue-400/50 w-3 h-3 sm:w-4 sm:h-4" />
                </div>
              </div>
              
              <!-- Completed Stage -->
              <div 
                class="flex-1 group cursor-pointer"
                @click="navigateToTickets('completed')"
              >
                <div 
                  class="relative rounded-lg sm:rounded-xl bg-gradient-to-b from-emerald-400 to-emerald-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-emerald-500/20"
                  :style="{ minHeight: `${Math.max(50, completedPercent * 1.2)}px` }"
                >
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-1.5 sm:p-2">
                    <Icon name="check-circle" size="sm" class="mb-0.5 sm:mb-1 opacity-90 sm:w-5 sm:h-5" />
                    <span class="text-base sm:text-lg lg:text-xl font-bold">{{ metrics.tickets?.completed || 0 }}</span>
                    <span class="text-[8px] sm:text-[10px] font-medium opacity-80 text-center leading-tight">{{ $t('dashboard.completed') }}</span>
                  </div>
                  <!-- Tooltip - hidden on mobile -->
                  <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-emerald-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                    {{ completedPercent }}% · {{ $t('commandCenter.clickToView') }}
                  </div>
                </div>
                <!-- Success indicator -->
                <div class="flex justify-center mt-0.5 sm:mt-1">
                  <Icon name="check" size="xs" class="text-emerald-400/50 w-3 h-3 sm:w-4 sm:h-4" />
                </div>
              </div>
              
              <!-- Overdue Stage (Alert) -->
              <div 
                class="flex-1 group cursor-pointer"
                @click="navigateToTickets('overdue')"
              >
                <div 
                  class="relative rounded-lg sm:rounded-xl bg-gradient-to-b from-red-400 to-red-500 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-red-500/20"
                  :class="{ 'animate-pulse': (metrics.tickets?.overdue || 0) > 0 }"
                  :style="{ minHeight: `${Math.max(50, overduePercent * 1.2)}px` }"
                >
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-white p-1.5 sm:p-2">
                    <Icon name="exclamation-triangle" size="sm" class="mb-0.5 sm:mb-1 opacity-90 sm:w-5 sm:h-5" />
                    <span class="text-base sm:text-lg lg:text-xl font-bold">{{ metrics.tickets?.overdue || 0 }}</span>
                    <span class="text-[8px] sm:text-[10px] font-medium opacity-80 text-center leading-tight">{{ $t('dashboard.overdueTickets') }}</span>
                  </div>
                  <!-- Tooltip - hidden on mobile -->
                  <div class="hidden sm:block absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-red-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                    {{ overduePercent }}% · {{ $t('commandCenter.clickToView') }}
                  </div>
                </div>
                <!-- Warning indicator -->
                <div class="flex justify-center mt-0.5 sm:mt-1">
                  <Icon name="exclamation" size="xs" :class="(metrics.tickets?.overdue || 0) > 0 ? 'text-red-500' : 'text-red-400/50'" class="w-3 h-3 sm:w-4 sm:h-4" />
                </div>
              </div>
            </div>
          </div>
          
          <!-- Ticket Sources - Split Intelligence Panel -->
          <div class="pt-3 sm:pt-4 border-t border-[var(--color-border)]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-0 mb-3 sm:mb-4">
              <p class="text-xs sm:text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('commandCenter.ticketSources') }}</p>
              <span class="text-xs text-[var(--color-text-muted)]">{{ sourceTotal }} {{ $t('dashboard.totalTickets') }}</span>
            </div>
            
            <!-- Split Panels - Stack on mobile, row on larger screens -->
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 min-h-[100px] sm:min-h-[120px]">
              <!-- Manual Panel -->
              <div 
                class="group relative rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-sky-500/30 overflow-hidden"
                :style="{ flex: `${Math.max(manualPercent, 20)} 1 0` }"
                @click="navigateToTickets('source=manual')"
              >
                <!-- Decorative Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                  <div class="absolute top-4 end-4 w-24 h-24 border-4 border-white rounded-full"></div>
                  <div class="absolute bottom-2 start-2 w-16 h-16 border-4 border-white rounded-full"></div>
                </div>
                
                <!-- Content -->
                <div class="relative h-full flex flex-col items-center justify-center p-3 sm:p-4 text-white">
                  <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/20 flex items-center justify-center mb-1.5 sm:mb-2 group-hover:scale-110 transition-transform">
                    <Icon name="user" size="md" class="sm:w-6 sm:h-6" />
                  </div>
                  <span class="text-2xl sm:text-3xl font-bold">{{ insights.source_breakdown?.manual || 0 }}</span>
                  <span class="text-xs sm:text-sm font-medium opacity-90">{{ $t('commandCenter.manual') }}</span>
                  <span class="text-[9px] sm:text-[10px] mt-1 px-2 py-0.5 rounded-full bg-white/20">{{ manualPercent }}%</span>
                </div>
                
                <!-- Hover Tooltip -->
                <div class="absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-sky-700 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                  {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
              
              <!-- Chatbot Panel -->
              <div 
                class="group relative rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-emerald-500/30 overflow-hidden"
                :style="{ flex: `${Math.max(chatbotPercent, 20)} 1 0` }"
                @click="navigateToTickets('source=chatbot')"
              >
                <!-- Decorative Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                  <div class="absolute top-4 start-4 w-20 h-20 border-4 border-white rounded-xl rotate-12"></div>
                  <div class="absolute bottom-4 end-4 w-12 h-12 border-4 border-white rounded-xl -rotate-12"></div>
                </div>
                
                <!-- Content -->
                <div class="relative h-full flex flex-col items-center justify-center p-3 sm:p-4 text-white">
                  <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/20 flex items-center justify-center mb-1.5 sm:mb-2 group-hover:scale-110 transition-transform">
                    <Icon name="robot" size="md" class="sm:w-6 sm:h-6" />
                  </div>
                  <span class="text-2xl sm:text-3xl font-bold">{{ insights.source_breakdown?.chatbot || 0 }}</span>
                  <span class="text-xs sm:text-sm font-medium opacity-90">{{ $t('commandCenter.chatbot') }}</span>
                  <span class="text-[9px] sm:text-[10px] mt-1 px-2 py-0.5 rounded-full bg-white/20">{{ chatbotPercent }}%</span>
                </div>
                
                <!-- Hover Tooltip -->
                <div class="absolute -top-2 left-1/2 -translate-x-1/2 -translate-y-full px-3 py-1.5 bg-emerald-700 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20 shadow-lg pointer-events-none">
                  {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ⚠️ ACTION REQUIRED - Theme-Adaptive, Urgent, Alive -->
      <div class="relative overflow-hidden rounded-xl sm:rounded-2xl p-4 sm:p-5 lg:p-6 shadow-xl border-2 border-red-200 dark:border-red-900/50 bg-gradient-to-br from-red-50 via-amber-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
        <!-- Animated background glow -->
        <div class="absolute inset-0 bg-gradient-to-r from-red-500/5 via-amber-500/5 to-blue-500/5 dark:from-red-500/10 dark:via-amber-500/10 dark:to-blue-500/10 animate-pulse"></div>
        
        <h3 class="relative font-bold text-red-900 dark:text-white mb-4 sm:mb-6 flex flex-wrap items-center gap-2 sm:gap-3 text-base sm:text-lg">
          <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/30">
            <Icon name="exclamation-circle" size="md" class="text-white sm:w-6 sm:h-6" :class="{ 'animate-pulse': hasUrgentItems }" />
          </div>
          {{ $t('commandCenter.actionRequired') }}
          <span v-if="hasUrgentItems" class="ml-auto px-3 py-1 rounded-full bg-red-500 text-white text-xs font-bold animate-pulse shadow-lg shadow-red-500/40">
            {{ $t('commandCenter.needsAttention') }}
          </span>
        </h3>
        
        <div class="relative grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
          <!-- 🔴 SLA At Risk - RED DOMINANT with GLOW -->
          <NuxtLink 
            to="/admin/sla-monitor"
            class="group relative overflow-hidden rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02] sm:hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="slaCritical + slaWarning > 0 
              ? 'bg-gradient-to-br from-red-600 via-red-500 to-rose-600 border-red-400 shadow-lg shadow-red-500/40' 
              : 'bg-gradient-to-br from-emerald-500 to-emerald-600 border-emerald-400 shadow-lg shadow-emerald-500/30'"
          >
            <!-- Pulse glow for critical items -->
            <div v-if="slaCritical > 0" class="absolute inset-0 bg-red-400/30 animate-pulse"></div>
            
            <div class="relative p-3 sm:p-4 lg:p-5">
              <div class="flex items-start justify-between mb-2 sm:mb-3 lg:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-lg sm:rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="clock" size="md" class="text-white drop-shadow sm:w-6 sm:h-6 lg:w-7 lg:h-7" :class="{ 'animate-pulse': slaCritical > 0 }" />
                </div>
                <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-white drop-shadow-lg">
                  {{ slaCritical + slaWarning }}
                </span>
              </div>
              <p class="text-white font-semibold text-xs sm:text-sm drop-shadow">{{ $t('commandCenter.slaAtRisk') }}</p>
              <p class="text-white/80 text-[10px] sm:text-xs mt-0.5 sm:mt-1">{{ slaCritical + slaWarning > 0 ? $t('commandCenter.within30Min') : $t('commandCenter.allClear') }}</p>
              <div class="absolute bottom-2 sm:bottom-3 end-2 sm:end-3">
                <Icon name="arrow-right" size="xs" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all sm:w-4 sm:h-4" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 🟠 Emergency Pending - AMBER/ORANGE with WARNING GLOW -->
          <NuxtLink 
            to="/admin/tickets?priority=urgent"
            class="group relative overflow-hidden rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02] sm:hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="(metrics.tickets?.overdue || 0) > 0 
              ? 'bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 border-amber-400 shadow-lg shadow-amber-500/40' 
              : 'bg-gradient-to-br from-emerald-500 to-emerald-600 border-emerald-400 shadow-lg shadow-emerald-500/30'"
          >
            <div v-if="(metrics.tickets?.overdue || 0) > 0" class="absolute inset-0 bg-amber-300/30 animate-pulse"></div>
            
            <div class="relative p-3 sm:p-4 lg:p-5">
              <div class="flex items-start justify-between mb-2 sm:mb-3 lg:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-lg sm:rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="exclamation-triangle" size="md" class="text-white drop-shadow sm:w-6 sm:h-6 lg:w-7 lg:h-7" :class="{ 'animate-bounce': (metrics.tickets?.overdue || 0) > 0 }" />
                </div>
                <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.overdue || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-xs sm:text-sm drop-shadow">{{ $t('commandCenter.emergencyPending') }}</p>
              <p class="text-white/80 text-[10px] sm:text-xs mt-0.5 sm:mt-1">{{ (metrics.tickets?.overdue || 0) > 0 ? $t('commandCenter.urgentPriority') : $t('commandCenter.allClear') }}</p>
              <div class="absolute bottom-2 sm:bottom-3 end-2 sm:end-3">
                <Icon name="arrow-right" size="xs" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all sm:w-4 sm:h-4" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 💳 Awaiting Payment - BLUE/CYAN ACTIONABLE -->
          <NuxtLink 
            to="/admin/tickets?status=awaiting_payment"
            class="group relative overflow-hidden rounded-lg sm:rounded-xl transition-all duration-300 hover:scale-[1.02] sm:hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="(metrics.tickets?.pending || 0) > 0 
              ? 'bg-gradient-to-br from-blue-500 via-cyan-500 to-blue-600 border-blue-400 shadow-lg shadow-blue-500/40' 
              : 'bg-gradient-to-br from-slate-500 to-slate-600 border-slate-400 shadow-lg shadow-slate-500/30'"
          >
            <div class="relative p-3 sm:p-4 lg:p-5">
              <div class="flex items-start justify-between mb-2 sm:mb-3 lg:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-lg sm:rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="credit-card" size="md" class="text-white drop-shadow sm:w-6 sm:h-6 lg:w-7 lg:h-7" />
                </div>
                <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.pending || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-xs sm:text-sm drop-shadow">{{ $t('commandCenter.awaitingPayment') }}</p>
              <p class="text-white/80 text-[10px] sm:text-xs mt-0.5 sm:mt-1">{{ $t('commandCenter.receptionAction') }}</p>
              <div class="absolute bottom-2 sm:bottom-3 end-2 sm:end-3">
                <Icon name="arrow-right" size="xs" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all sm:w-4 sm:h-4" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 📅 Today Summary - PURPLE NEUTRAL -->
          <NuxtLink 
            to="/admin/tickets"
            class="group relative overflow-hidden rounded-lg sm:rounded-xl bg-gradient-to-br from-violet-500 via-purple-500 to-fuchsia-600 border-2 border-purple-400 shadow-lg shadow-purple-500/30 transition-all duration-300 hover:scale-[1.02] sm:hover:scale-[1.03] hover:shadow-2xl"
          >
            <div class="relative p-3 sm:p-4 lg:p-5">
              <div class="flex items-start justify-between mb-2 sm:mb-3 lg:mb-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 rounded-lg sm:rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="calendar" size="md" class="text-white drop-shadow sm:w-6 sm:h-6 lg:w-7 lg:h-7" />
                </div>
                <span class="text-2xl sm:text-3xl lg:text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.total || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-xs sm:text-sm drop-shadow">{{ $t('commandCenter.todaySummary') }}</p>
              <p class="text-white/80 text-[10px] sm:text-xs mt-0.5 sm:mt-1">{{ metrics.tickets?.completed || 0 }} {{ $t('commandCenter.completed') }} · {{ todayCompletionRate }}%</p>
              <div class="absolute bottom-2 sm:bottom-3 end-2 sm:end-3">
                <Icon name="arrow-right" size="xs" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all sm:w-4 sm:h-4" />
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
      
      <!-- 🛡️ SYSTEM HEALTH - Calm, Stable, Reassuring -->
      <div class="rounded-xl sm:rounded-2xl bg-gradient-to-br from-teal-50 via-cyan-50 to-sky-50 dark:from-teal-950/30 dark:via-cyan-950/30 dark:to-sky-950/30 p-4 sm:p-5 lg:p-6 border border-teal-200/50 dark:border-teal-800/30">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 mb-4 sm:mb-6">
          <h3 class="font-semibold text-teal-800 dark:text-teal-200 flex flex-wrap items-center gap-2 sm:gap-3 text-sm sm:text-base">
            <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-md sm:rounded-lg bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center">
              <Icon name="shield-check" class="text-white w-4 h-4 sm:w-5 sm:h-5" />
            </div>
            {{ $t('commandCenter.systemHealth') }}
            <!-- All Systems OK indicator -->
            <span v-if="allSystemsHealthy" class="px-2 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 text-[10px] sm:text-xs font-medium">
              ✓ {{ $t('commandCenter.allSystemsOk') }}
            </span>
          </h3>
          <NuxtLink 
            to="/admin/system-health"
            class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 transition-colors"
          >
            {{ $t('commandCenter.viewDetails') }}
            <Icon name="arrow-right" size="xs" />
          </NuxtLink>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Database -->
          <div 
            class="group p-4 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center">
                <Icon name="database" class="text-teal-600 dark:text-teal-400" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $t('commandCenter.database') }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span 
                    class="w-2 h-2 rounded-full"
                    :class="systemHealth.database?.status === 'healthy' ? 'bg-teal-500' : systemHealth.database?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-xs" :class="systemHealth.database?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.database?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.database?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Cache -->
          <div 
            class="group p-2.5 sm:p-3 lg:p-4 rounded-lg sm:rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-2 sm:gap-3">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-md sm:rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center shrink-0">
                <Icon name="bolt" class="text-teal-600 dark:text-teal-400 w-4 h-4 sm:w-5 sm:h-5" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ $t('commandCenter.cache') }}</p>
                <div class="flex items-center gap-1 sm:gap-1.5 mt-0.5">
                  <span 
                    class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full"
                    :class="systemHealth.cache?.status === 'healthy' ? 'bg-teal-500' : systemHealth.cache?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-[10px] sm:text-xs" :class="systemHealth.cache?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.cache?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.cache?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Queue -->
          <div 
            class="group p-2.5 sm:p-3 lg:p-4 rounded-lg sm:rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-2 sm:gap-3">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-md sm:rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center shrink-0">
                <Icon name="list" class="text-teal-600 dark:text-teal-400 w-4 h-4 sm:w-5 sm:h-5" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ $t('commandCenter.queue') }}</p>
                <div class="flex items-center gap-1 sm:gap-1.5 mt-0.5">
                  <span 
                    class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full"
                    :class="systemHealth.queue?.status === 'healthy' ? 'bg-teal-500' : systemHealth.queue?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-[10px] sm:text-xs" :class="systemHealth.queue?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.queue?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.queue?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Storage -->
          <div 
            class="group p-2.5 sm:p-3 lg:p-4 rounded-lg sm:rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-2 sm:gap-3">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-md sm:rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center shrink-0">
                <Icon name="folder" class="text-teal-600 dark:text-teal-400 w-4 h-4 sm:w-5 sm:h-5" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ $t('commandCenter.storage') }}</p>
                <div class="flex items-center gap-1 sm:gap-1.5 mt-0.5">
                  <span 
                    class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full"
                    :class="systemHealth.storage?.status === 'healthy' ? 'bg-teal-500' : systemHealth.storage?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-[10px] sm:text-xs" :class="systemHealth.storage?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.storage?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.storage?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['auth'] })

const config = useRuntimeConfig()
const { token } = useAuth()
const { locale } = useI18n()

const loading = ref(false)
const selectedStat = ref<string | null>(null)

const metrics = ref<any>({})
const insights = ref<any>({})

// Source percentages
const sourceTotal = computed(() => (insights.value.source_breakdown?.manual || 0) + (insights.value.source_breakdown?.chatbot || 0) || 1)
const manualPercent = computed(() => Math.round((insights.value.source_breakdown?.manual || 0) / sourceTotal.value * 100))
const chatbotPercent = computed(() => Math.round((insights.value.source_breakdown?.chatbot || 0) / sourceTotal.value * 100))

// Today's completion rate - using metrics data
const todayCompletionRate = computed(() => {
  const total = metrics.value.tickets?.total || 0
  const completed = metrics.value.tickets?.completed || 0
  if (total === 0) return 0
  return Math.round((completed / total) * 100)
})

// SLA Status computed (from insights data)
// Critical = tickets with deadline < 15 min from now
// Warning = tickets with deadline 15-30 min from now  
// On Track = all other active tickets
const slaCritical = computed(() => {
  // Backend at_risk is for < 30 min, we estimate critical as ~50% of at_risk
  const atRisk = insights.value.at_risk?.count || 0
  return Math.ceil(atRisk * 0.5)
})

const slaWarning = computed(() => {
  const atRisk = insights.value.at_risk?.count || 0
  return atRisk - slaCritical.value
})

const slaOnTrack = computed(() => {
  const pending = metrics.value.tickets?.pending || 0
  const inProgress = metrics.value.tickets?.in_progress || 0
  const atRisk = insights.value.at_risk?.count || 0
  return Math.max(0, pending + inProgress - atRisk)
})

// SLA Percentage calculations for segmented bar
const totalSlaTickets = computed(() => slaOnTrack.value + slaWarning.value + slaCritical.value || 1)
const slaOnTrackPercent = computed(() => Math.round((slaOnTrack.value / totalSlaTickets.value) * 100))
const slaWarningPercent = computed(() => Math.round((slaWarning.value / totalSlaTickets.value) * 100))
const slaCriticalPercent = computed(() => Math.round((slaCritical.value / totalSlaTickets.value) * 100))

// Check if any urgent items need attention
const hasUrgentItems = computed(() => {
  return slaCritical.value > 0 || slaWarning.value > 0 || (metrics.value.tickets?.overdue || 0) > 0
})

// Severity styling helpers
const currentDate = computed(() => 
  new Date().toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', { 
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
  })
)

const selectStat = (stat: string) => {
  selectedStat.value = selectedStat.value === stat ? null : stat
}

// System Health data
const systemHealth = ref<any>({
  database: { status: 'healthy' },
  cache: { status: 'healthy' },
  queue: { status: 'healthy' },
  storage: { status: 'healthy' }
})

const navigateToSystemHealth = () => {
  router.push('/admin/system-health')
}

const navigateToTickets = (status: string) => {
  router.push(`/admin/tickets?status=${status}`)
}

// Check if all systems are healthy
const allSystemsHealthy = computed(() => {
  return systemHealth.value.database?.status === 'healthy' &&
         systemHealth.value.cache?.status === 'healthy' &&
         systemHealth.value.queue?.status === 'healthy' &&
         systemHealth.value.storage?.status === 'healthy'
})

// Radial Chart for Department Distribution
const hoveredDept = ref<number | null>(null)
const router = useRouter()

const totalDeptTickets = computed(() => {
  const stats = metrics.value.departments_stats || []
  return stats.reduce((sum: number, d: any) => sum + (d.count || 0), 0)
})

// Calculate radial segments with arc positions
const radialDepartments = computed(() => {
  const stats = metrics.value.departments_stats || []
  // Sort by count descending - NO LIMIT to support unlimited departments
  const sorted = [...stats].sort((a: any, b: any) => b.count - a.count)
  const total = totalDeptTickets.value || 1
  const circumference = 502.65 // 2 * PI * 80
  
  let offset = 0
  return sorted.map((dept: any) => {
    const percentage = dept.count / total
    const arcLength = percentage * circumference
    const result = {
      ...dept,
      arcLength,
      offset,
      pressure: getDeptPressure(dept)
    }
    offset += arcLength
    return result
  })
})

// Determine pressure level based on overdue/open ratio
const getDeptPressure = (dept: any) => {
  const overdue = dept.overdue || 0
  const total = dept.count || 1
  const overdueRatio = overdue / total
  
  if (overdueRatio > 0.2) return 'high'
  if (overdueRatio > 0.1) return 'medium'
  return 'low'
}

// Legend progressive disclosure state
const DEFAULT_VISIBLE_DEPTS = 8
const legendMode = ref<'top' | 'all'>('top')
const legendExpanded = ref(false)

// Departments to show in legend (based on mode and expand state)
const visibleLegendDepts = computed(() => {
  if (legendMode.value === 'all' || legendExpanded.value) {
    return radialDepartments.value
  }
  return radialDepartments.value.slice(0, DEFAULT_VISIBLE_DEPTS)
})

// Count of hidden departments when in collapsed mode
const hiddenDeptCount = computed(() => 
  Math.max(0, radialDepartments.value.length - DEFAULT_VISIBLE_DEPTS)
)

// Expanded color palette for unlimited departments (12 distinct colors)
const deptColors = [
  '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', 
  '#ec4899', '#06b6d4', '#84cc16', '#f97316',
  '#14b8a6', '#6366f1', '#ef4444', '#0ea5e9'
]

const getDeptColor = (index: number, pressure: string) => {
  if (pressure === 'high') return '#ef4444'
  if (pressure === 'medium') return '#f59e0b'
  return deptColors[index % deptColors.length]
}

// Department → Icon mapping (semantic icons for known departments)
const deptIconMap: Record<string, string> = {
  'emergency': 'ambulance',
  'pediatrics': 'baby',
  'cardiology': 'heart-pulse',
  'ophthalmology': 'eye',
  'neurology': 'brain',
  'gastroenterology': 'stomach',
  'orthopedics': 'bone',
  'dermatology': 'hand-dots',
  'ent': 'ear',
  'internal-medicine': 'stethoscope',
  'pulmonology': 'lungs',
  'urology': 'droplet',
  'nephrology': 'kidneys',
  'psychiatry': 'brain',
  'obstetrics': 'person-pregnant',
  'oncology': 'ribbon',
  'radiology': 'x-ray',
  'laboratory': 'flask',
  'pharmacy': 'pills'
}

const getDeptIcon = (slug: string | undefined): string => {
  if (!slug) return 'building'
  const normalizedSlug = slug.toLowerCase().replace(/\s+/g, '-')
  return deptIconMap[normalizedSlug] || 'building'
}

const getHoveredDeptData = computed(() => {
  if (!hoveredDept.value) return null
  return radialDepartments.value.find((d: any) => d.id === hoveredDept.value) || null
})

const navigateToDeptTickets = (deptId: number) => {
  router.push(`/admin/tickets?department=${deptId}`)
}

// Pie chart percentages
const totalTickets = computed(() => metrics.value.tickets?.total || 1)
const completedPercent = computed(() => Math.round((metrics.value.tickets?.completed || 0) / totalTickets.value * 100))
const pendingPercent = computed(() => Math.round((metrics.value.tickets?.pending || 0) / totalTickets.value * 100))
const inProgressPercent = computed(() => Math.round((metrics.value.tickets?.in_progress || 0) / totalTickets.value * 100))
const overduePercent = computed(() => Math.max(0, 100 - completedPercent.value - pendingPercent.value - inProgressPercent.value))

// Ticket Sources computed

const fetchMetrics = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/admin/metrics`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    metrics.value = res
  } catch (e) {
    console.error('Failed to fetch metrics:', e)
  }
}

const fetchInsights = async () => {
  try {
    const res = await $fetch<any>(`${config.public.apiBase}/admin/insights`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
    insights.value = res
  } catch (e) {
    console.error('Failed to fetch insights:', e)
  }
}

const refreshAll = async () => {
  loading.value = true
  try {
    await Promise.all([fetchMetrics(), fetchInsights()])
  } finally {
    loading.value = false
  }
}

onMounted(refreshAll)
</script>

<style scoped>
.bounce-enter-active {
  animation: bounce-in 0.5s;
}
.bounce-leave-active {
  animation: bounce-in 0.3s reverse;
}
@keyframes bounce-in {
  0% { transform: scale(0.5); opacity: 0; }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); opacity: 1; }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes dash {
  to { stroke-dashoffset: -24; }
}
.animate-dash {
  animation: dash 1s linear infinite;
}
.animate-dash-reverse {
  animation: dash 1s linear infinite reverse;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}
.animate-float {
  animation: float 2s ease-in-out infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}
.animate-blink {
  animation: blink 1.5s ease-in-out infinite;
}
</style>
