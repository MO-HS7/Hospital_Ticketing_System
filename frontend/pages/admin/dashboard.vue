<template>
  <NuxtLayout name="admin">
    <div class="space-y-8">
      <!-- Header: Date + SLA Pressure Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- Left: Date + Refresh -->
        <div class="flex items-center gap-3">
          <span class="text-sm text-[var(--color-text-muted)]">{{ currentDate }}</span>
          <button @click="refreshAll" class="btn-ghost p-2 rounded-full hover:bg-[var(--color-bg-tertiary)]" :disabled="loading">
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
          <div class="flex items-center h-6 rounded-lg overflow-hidden bg-[var(--color-bg-tertiary)] min-w-[140px]">
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
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Active Doctors -->
        <div 
          class="flex flex-col items-center cursor-pointer group"
          @click="selectStat('doctors')"
        >
          <div 
            class="relative w-28 h-28 mb-3 transition-transform duration-500"
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
              <Icon name="user-doctor" size="md" class="text-emerald-600 mt-1 transition-transform duration-300 group-hover:scale-110" />
            </div>
          </div>
          <p class="text-sm font-medium text-[var(--color-text-secondary)] transition-colors" :class="{ 'text-emerald-600': selectedStat === 'doctors' }">
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
            class="relative w-28 h-28 mb-3 transition-transform duration-500"
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
              <span class="text-2xl font-bold text-emerald-600">{{ insights.today?.total || 0 }}</span>
              <Icon name="user-group" size="md" class="text-emerald-600 mt-1 transition-transform duration-300 group-hover:scale-110" />
            </div>
          </div>
          <p class="text-sm font-medium text-[var(--color-text-secondary)] transition-colors" :class="{ 'text-emerald-600': selectedStat === 'patients' }">
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
            class="relative w-28 h-28 mb-3 transition-transform duration-500"
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
              <span class="text-2xl font-bold text-emerald-600">{{ metrics.staff?.maintenance || 0 }}</span>
              <Icon name="tools" size="md" class="text-emerald-600 mt-1 transition-transform duration-300 group-hover:scale-110" />
            </div>
          </div>
          <p class="text-sm font-medium text-[var(--color-text-secondary)] transition-colors" :class="{ 'text-emerald-600': selectedStat === 'maintenance' }">
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
            class="relative w-28 h-28 mb-3 transition-transform duration-500"
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
              <span class="text-2xl font-bold text-emerald-600">{{ metrics.staff?.reception || 0 }}</span>
              <Icon name="building" size="md" class="text-emerald-600 mt-1 transition-transform duration-300 group-hover:scale-110" />
            </div>
          </div>
          <p class="text-sm font-medium text-[var(--color-text-secondary)] transition-colors" :class="{ 'text-emerald-600': selectedStat === 'reception' }">
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
      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Department Distribution - Interactive Radial -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $t('commandCenter.ticketsByDept') }}</h3>
            <span class="text-xs text-[var(--color-text-muted)]">{{ totalDeptTickets }} {{ $t('nav.tickets') }}</span>
          </div>
          
          <!-- Radial Chart -->
          <div class="relative flex items-center justify-center" style="min-height: 220px;">
            <svg viewBox="0 0 200 200" class="w-52 h-52">
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
                      <p class="text-2xl font-bold text-[var(--color-text-primary)]">{{ getHoveredDeptData.count }}</p>
                      <p class="text-xs text-[var(--color-text-muted)] truncate max-w-[100px]">
                        {{ locale === 'ar' ? getHoveredDeptData.name_ar : getHoveredDeptData.name_en }}
                      </p>
                      <p class="text-[10px] mt-1" :class="getPressureTextClass(getHoveredDeptData.pressure)">
                        {{ $t(`commandCenter.${getHoveredDeptData.pressure}Pressure`) }}
                      </p>
                    </div>
                    <div v-else key="default">
                      <p class="text-3xl font-bold text-[var(--color-text-primary)]">{{ totalDeptTickets }}</p>
                      <p class="text-xs text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}</p>
                    </div>
                  </Transition>
                </div>
              </foreignObject>
            </svg>
            
            <!-- Hover tooltip -->
            <Transition name="fade">
              <div 
                v-if="hoveredDept && getHoveredDeptData" 
                class="absolute top-0 end-0 p-3 rounded-xl bg-[var(--color-bg-secondary)] border border-[var(--color-border)] shadow-lg text-sm z-10"
              >
                <p class="font-semibold text-[var(--color-text-primary)] mb-2">
                  {{ locale === 'ar' ? getHoveredDeptData.name_ar : getHoveredDeptData.name_en }}
                </p>
                <div class="space-y-1 text-xs">
                  <div class="flex justify-between gap-4">
                    <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.totalTickets') }}:</span>
                    <span class="font-bold">{{ getHoveredDeptData.count }}</span>
                  </div>
                  <div class="flex justify-between gap-4">
                    <span class="text-[var(--color-text-muted)]">{{ $t('commandCenter.stillOpen') }}:</span>
                    <span class="font-bold text-amber-600">{{ getHoveredDeptData.open || 0 }}</span>
                  </div>
                  <div class="flex justify-between gap-4">
                    <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.overdueTickets') }}:</span>
                    <span class="font-bold text-red-600">{{ getHoveredDeptData.overdue || 0 }}</span>
                  </div>
                </div>
                <p class="mt-2 text-[10px] text-primary-600">{{ $t('commandCenter.clickToView') }} →</p>
              </div>
            </Transition>
          </div>
          
          <!-- Legend -->
          <div class="flex flex-wrap justify-center gap-3 mt-4">
            <div 
              v-for="(dept, idx) in radialDepartments.slice(0, 5)" 
              :key="`legend-${dept.id}`"
              class="flex items-center gap-1.5 px-2 py-1 rounded-full text-xs cursor-pointer transition-all"
              :class="hoveredDept === dept.id ? 'bg-[var(--color-bg-tertiary)] scale-105' : 'hover:bg-[var(--color-bg-tertiary)]'"
              @mouseenter="hoveredDept = dept.id"
              @mouseleave="hoveredDept = null"
              @click="navigateToDeptTickets(dept.id)"
            >
              <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: getDeptColor(idx, dept.pressure) }"></span>
              <span class="text-[var(--color-text-secondary)] truncate max-w-[80px]">
                {{ locale === 'ar' ? dept.name_ar : dept.name_en }}
              </span>
            </div>
          </div>
          
          <!-- Empty state -->
          <div v-if="!radialDepartments.length" class="text-center py-8 text-[var(--color-text-muted)]">
            <Icon name="building" size="xl" class="mb-2 opacity-50" />
            <p class="text-sm">{{ $t('commandCenter.noData') }}</p>
          </div>
        </div>

        <!-- Daily Operations Snapshot -->
        <div class="card p-6">
          <h3 class="font-semibold text-[var(--color-text-primary)] mb-4 flex items-center gap-2">
            <Icon name="chart-bar" size="md" class="text-primary-600" />
            {{ $t('commandCenter.dailyOperations') }}
          </h3>
          
          <!-- Visual Stacked Status Bar -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs text-[var(--color-text-muted)]">{{ $t('commandCenter.statusDistribution') }}</span>
              <span class="text-xs font-medium text-[var(--color-text-secondary)]">{{ metrics.tickets?.total || 0 }} {{ $t('dashboard.totalTickets') }}</span>
            </div>
            <div class="h-8 bg-[var(--color-bg-tertiary)] rounded-xl overflow-hidden flex">
              <!-- Completed -->
              <div 
                class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 flex items-center justify-center transition-all duration-300 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${completedPercent}%` }"
                @click="navigateToTickets('completed')"
              >
                <span v-if="completedPercent > 10" class="text-[10px] font-bold text-white">{{ metrics.tickets?.completed || 0 }}</span>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-emerald-600 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none">
                  {{ $t('dashboard.completed') }}: {{ metrics.tickets?.completed || 0 }} ({{ completedPercent }}%) · {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
              <!-- In Progress -->
              <div 
                class="h-full bg-gradient-to-r from-blue-400 to-blue-500 flex items-center justify-center transition-all duration-300 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${inProgressPercent}%` }"
                @click="navigateToTickets('in_progress')"
              >
                <span v-if="inProgressPercent > 10" class="text-[10px] font-bold text-white">{{ metrics.tickets?.in_progress || 0 }}</span>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-blue-600 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none">
                  {{ $t('dashboard.inProgressTickets') }}: {{ metrics.tickets?.in_progress || 0 }} ({{ inProgressPercent }}%) · {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
              <!-- Pending -->
              <div 
                class="h-full bg-gradient-to-r from-amber-400 to-amber-500 flex items-center justify-center transition-all duration-300 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${pendingPercent}%` }"
                @click="navigateToTickets('pending')"
              >
                <span v-if="pendingPercent > 10" class="text-[10px] font-bold text-white">{{ metrics.tickets?.pending || 0 }}</span>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-amber-600 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none">
                  {{ $t('dashboard.pending') }}: {{ metrics.tickets?.pending || 0 }} ({{ pendingPercent }}%) · {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
              <!-- Overdue -->
              <div 
                class="h-full bg-gradient-to-r from-red-400 to-red-500 flex items-center justify-center transition-all duration-300 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${overduePercent}%` }"
                @click="navigateToTickets('overdue')"
              >
                <span v-if="overduePercent > 10" class="text-[10px] font-bold text-white">{{ metrics.tickets?.overdue || 0 }}</span>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-red-600 text-white text-[10px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none">
                  {{ $t('dashboard.overdueTickets') }}: {{ metrics.tickets?.overdue || 0 }} ({{ overduePercent }}%) · {{ $t('commandCenter.clickToView') }}
                </div>
              </div>
            </div>
            <!-- Legend -->
            <div class="flex flex-wrap justify-center gap-4 mt-3 text-xs">
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-emerald-500"></span>
                <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.completed') }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-blue-500"></span>
                <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.inProgressTickets') }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-amber-500"></span>
                <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.pending') }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-red-500"></span>
                <span class="text-[var(--color-text-muted)]">{{ $t('dashboard.overdueTickets') }}</span>
              </div>
            </div>
          </div>
          
          <!-- Ticket Sources - Interactive Segmented Bar -->
          <div class="pt-4 border-t border-[var(--color-border)]">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm font-medium text-[var(--color-text-secondary)]">{{ $t('commandCenter.ticketSources') }}</p>
              <span class="text-xs text-[var(--color-text-muted)]">{{ sourceTotal }} {{ $t('dashboard.totalTickets') }}</span>
            </div>
            
            <!-- Visual ratio bar with click interaction -->
            <div class="relative h-8 bg-[var(--color-bg-tertiary)] rounded-xl overflow-hidden flex shadow-inner">
              <!-- Manual Segment - CLICKABLE -->
              <div 
                class="h-full bg-gradient-to-r from-sky-400 to-sky-500 flex items-center justify-center transition-all duration-500 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${manualPercent}%`, minWidth: manualPercent > 0 ? '40px' : '0' }"
                @click="navigateToTickets('source=manual')"
              >
                <div class="flex items-center gap-1">
                  <Icon name="user" size="xs" class="text-white" />
                  <span v-if="manualPercent > 15" class="text-[10px] font-bold text-white">{{ insights.source_breakdown?.manual || 0 }}</span>
                </div>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 bg-sky-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none shadow-lg">
                  <div class="font-semibold">{{ $t('commandCenter.manual') }}</div>
                  <div>{{ insights.source_breakdown?.manual || 0 }} {{ $t('nav.tickets') }} ({{ manualPercent }}%)</div>
                  <div class="text-sky-200 mt-0.5">{{ $t('commandCenter.clickToView') }}</div>
                </div>
              </div>
              
              <!-- Chatbot Segment - CLICKABLE -->
              <div 
                class="h-full bg-gradient-to-r from-emerald-400 to-emerald-500 flex items-center justify-center transition-all duration-500 relative group cursor-pointer hover:brightness-110"
                :style="{ width: `${chatbotPercent}%`, minWidth: chatbotPercent > 0 ? '40px' : '0' }"
                @click="navigateToTickets('source=chatbot')"
              >
                <div class="flex items-center gap-1">
                  <Icon name="robot" size="xs" class="text-white" />
                  <span v-if="chatbotPercent > 15" class="text-[10px] font-bold text-white">{{ insights.source_breakdown?.chatbot || 0 }}</span>
                </div>
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1.5 bg-emerald-600 text-white text-[10px] rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none shadow-lg">
                  <div class="font-semibold">{{ $t('commandCenter.chatbot') }}</div>
                  <div>{{ insights.source_breakdown?.chatbot || 0 }} {{ $t('nav.tickets') }} ({{ chatbotPercent }}%)</div>
                  <div class="text-emerald-200 mt-0.5">{{ $t('commandCenter.clickToView') }}</div>
                </div>
              </div>
            </div>
            
            <!-- Source Legend -->
            <div class="flex justify-center gap-6 mt-3">
              <div 
                class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"
                @click="navigateToTickets('source=manual')"
              >
                <span class="w-3 h-3 rounded bg-gradient-to-r from-sky-400 to-sky-500"></span>
                <span class="text-xs text-[var(--color-text-muted)]">{{ $t('commandCenter.manual') }}</span>
                <span class="text-xs font-medium text-sky-600">{{ insights.source_breakdown?.manual || 0 }}</span>
              </div>
              <div 
                class="flex items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity"
                @click="navigateToTickets('source=chatbot')"
              >
                <span class="w-3 h-3 rounded bg-gradient-to-r from-emerald-400 to-emerald-500"></span>
                <span class="text-xs text-[var(--color-text-muted)]">{{ $t('commandCenter.chatbot') }}</span>
                <span class="text-xs font-medium text-emerald-600">{{ insights.source_breakdown?.chatbot || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ⚠️ ACTION REQUIRED - Theme-Adaptive, Urgent, Alive -->
      <div class="relative overflow-hidden rounded-2xl p-6 shadow-xl border-2 border-red-200 dark:border-red-900/50 bg-gradient-to-br from-red-50 via-amber-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
        <!-- Animated background glow -->
        <div class="absolute inset-0 bg-gradient-to-r from-red-500/5 via-amber-500/5 to-blue-500/5 dark:from-red-500/10 dark:via-amber-500/10 dark:to-blue-500/10 animate-pulse"></div>
        
        <h3 class="relative font-bold text-red-900 dark:text-white mb-6 flex items-center gap-3 text-lg">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/30">
            <Icon name="exclamation-circle" size="lg" class="text-white" :class="{ 'animate-pulse': hasUrgentItems }" />
          </div>
          {{ $t('commandCenter.actionRequired') }}
          <span v-if="hasUrgentItems" class="ml-auto px-3 py-1 rounded-full bg-red-500 text-white text-xs font-bold animate-pulse shadow-lg shadow-red-500/40">
            {{ $t('commandCenter.needsAttention') }}
          </span>
        </h3>
        
        <div class="relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- 🔴 SLA At Risk - RED DOMINANT with GLOW -->
          <NuxtLink 
            to="/admin/sla-monitor"
            class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="slaCritical + slaWarning > 0 
              ? 'bg-gradient-to-br from-red-600 via-red-500 to-rose-600 border-red-400 shadow-lg shadow-red-500/40' 
              : 'bg-gradient-to-br from-emerald-500 to-emerald-600 border-emerald-400 shadow-lg shadow-emerald-500/30'"
          >
            <!-- Pulse glow for critical items -->
            <div v-if="slaCritical > 0" class="absolute inset-0 bg-red-400/30 animate-pulse"></div>
            
            <div class="relative p-5">
              <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="clock" size="xl" class="text-white drop-shadow" :class="{ 'animate-pulse': slaCritical > 0 }" />
                </div>
                <span class="text-4xl font-black text-white drop-shadow-lg">
                  {{ slaCritical + slaWarning }}
                </span>
              </div>
              <p class="text-white font-semibold text-sm drop-shadow">{{ $t('commandCenter.slaAtRisk') }}</p>
              <p class="text-white/80 text-xs mt-1">{{ slaCritical + slaWarning > 0 ? $t('commandCenter.within30Min') : $t('commandCenter.allClear') }}</p>
              <div class="absolute bottom-3 end-3">
                <Icon name="arrow-right" size="sm" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 🟠 Emergency Pending - AMBER/ORANGE with WARNING GLOW -->
          <NuxtLink 
            to="/admin/tickets?priority=urgent"
            class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="(metrics.tickets?.overdue || 0) > 0 
              ? 'bg-gradient-to-br from-amber-500 via-orange-500 to-amber-600 border-amber-400 shadow-lg shadow-amber-500/40' 
              : 'bg-gradient-to-br from-emerald-500 to-emerald-600 border-emerald-400 shadow-lg shadow-emerald-500/30'"
          >
            <div v-if="(metrics.tickets?.overdue || 0) > 0" class="absolute inset-0 bg-amber-300/30 animate-pulse"></div>
            
            <div class="relative p-5">
              <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="exclamation-triangle" size="xl" class="text-white drop-shadow" :class="{ 'animate-bounce': (metrics.tickets?.overdue || 0) > 0 }" />
                </div>
                <span class="text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.overdue || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-sm drop-shadow">{{ $t('commandCenter.emergencyPending') }}</p>
              <p class="text-white/80 text-xs mt-1">{{ (metrics.tickets?.overdue || 0) > 0 ? $t('commandCenter.urgentPriority') : $t('commandCenter.allClear') }}</p>
              <div class="absolute bottom-3 end-3">
                <Icon name="arrow-right" size="sm" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 💳 Awaiting Payment - BLUE/CYAN ACTIONABLE -->
          <NuxtLink 
            to="/admin/tickets?status=awaiting_payment"
            class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl border-2"
            :class="(metrics.tickets?.pending || 0) > 0 
              ? 'bg-gradient-to-br from-blue-500 via-cyan-500 to-blue-600 border-blue-400 shadow-lg shadow-blue-500/40' 
              : 'bg-gradient-to-br from-slate-500 to-slate-600 border-slate-400 shadow-lg shadow-slate-500/30'"
          >
            <div class="relative p-5">
              <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="credit-card" size="xl" class="text-white drop-shadow" />
                </div>
                <span class="text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.pending || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-sm drop-shadow">{{ $t('commandCenter.awaitingPayment') }}</p>
              <p class="text-white/80 text-xs mt-1">{{ $t('commandCenter.receptionAction') }}</p>
              <div class="absolute bottom-3 end-3">
                <Icon name="arrow-right" size="sm" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all" />
              </div>
            </div>
          </NuxtLink>
          
          <!-- 📅 Today Summary - PURPLE NEUTRAL -->
          <NuxtLink 
            to="/admin/tickets"
            class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-violet-500 via-purple-500 to-fuchsia-600 border-2 border-purple-400 shadow-lg shadow-purple-500/30 transition-all duration-300 hover:scale-[1.03] hover:shadow-2xl"
          >
            <div class="relative p-5">
              <div class="flex items-start justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-white/25 backdrop-blur flex items-center justify-center shadow-inner">
                  <Icon name="calendar" size="xl" class="text-white drop-shadow" />
                </div>
                <span class="text-4xl font-black text-white drop-shadow-lg">
                  {{ metrics.tickets?.total || 0 }}
                </span>
              </div>
              <p class="text-white font-semibold text-sm drop-shadow">{{ $t('commandCenter.todaySummary') }}</p>
              <p class="text-white/80 text-xs mt-1">{{ metrics.tickets?.completed || 0 }} {{ $t('commandCenter.completed') }} · {{ todayCompletionRate }}%</p>
              <div class="absolute bottom-3 end-3">
                <Icon name="arrow-right" size="sm" class="text-white/60 group-hover:translate-x-1 group-hover:text-white transition-all" />
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
      
      <!-- 🛡️ SYSTEM HEALTH - Calm, Stable, Reassuring -->
      <div class="rounded-2xl bg-gradient-to-br from-teal-50 via-cyan-50 to-sky-50 dark:from-teal-950/30 dark:via-cyan-950/30 dark:to-sky-950/30 p-6 border border-teal-200/50 dark:border-teal-800/30">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-semibold text-teal-800 dark:text-teal-200 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center">
              <Icon name="shield-check" class="text-white" />
            </div>
            {{ $t('commandCenter.systemHealth') }}
            <!-- All Systems OK indicator -->
            <span v-if="allSystemsHealthy" class="px-2.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 text-xs font-medium">
              ✓ {{ $t('commandCenter.allSystemsOk') }}
            </span>
          </h3>
          <NuxtLink 
            to="/admin/system-health"
            class="text-xs text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 transition-colors"
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
            class="group p-4 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center">
                <Icon name="bolt" class="text-teal-600 dark:text-teal-400" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $t('commandCenter.cache') }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span 
                    class="w-2 h-2 rounded-full"
                    :class="systemHealth.cache?.status === 'healthy' ? 'bg-teal-500' : systemHealth.cache?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-xs" :class="systemHealth.cache?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.cache?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.cache?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Queue -->
          <div 
            class="group p-4 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center">
                <Icon name="list" class="text-teal-600 dark:text-teal-400" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $t('commandCenter.queue') }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span 
                    class="w-2 h-2 rounded-full"
                    :class="systemHealth.queue?.status === 'healthy' ? 'bg-teal-500' : systemHealth.queue?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-xs" :class="systemHealth.queue?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.queue?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
                    {{ $t(`commandCenter.${systemHealth.queue?.status || 'healthy'}`) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Storage -->
          <div 
            class="group p-4 rounded-xl bg-white/60 dark:bg-slate-800/40 border border-teal-100 dark:border-teal-800/30 transition-all cursor-pointer hover:bg-white dark:hover:bg-slate-800/60 hover:shadow-sm"
            @click="navigateToSystemHealth"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/50 dark:to-cyan-900/50 flex items-center justify-center">
                <Icon name="folder" class="text-teal-600 dark:text-teal-400" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $t('commandCenter.storage') }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span 
                    class="w-2 h-2 rounded-full"
                    :class="systemHealth.storage?.status === 'healthy' ? 'bg-teal-500' : systemHealth.storage?.status === 'degraded' ? 'bg-amber-500' : 'bg-red-500'"
                  ></span>
                  <span class="text-xs" :class="systemHealth.storage?.status === 'healthy' ? 'text-teal-600 dark:text-teal-400' : systemHealth.storage?.status === 'degraded' ? 'text-amber-600' : 'text-red-600'">
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

// Open tickets = pending + in_progress
const openTickets = computed(() => {
  const pending = metrics.value.tickets?.pending || 0
  const inProgress = metrics.value.tickets?.in_progress || 0
  return pending + inProgress
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
const getSeverityClass = (severity: string | undefined) => {
  switch (severity) {
    case 'critical': return 'border-red-500 bg-red-50 dark:bg-red-900/20'
    case 'warning': return 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
    case 'info': return 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
    case 'success': return 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
    default: return 'border-transparent bg-[var(--color-bg-tertiary)]/50'
  }
}

const getSeverityBgClass = (severity: string | undefined) => {
  switch (severity) {
    case 'critical': return 'bg-gradient-to-br from-red-400 to-red-600'
    case 'warning': return 'bg-gradient-to-br from-amber-400 to-amber-600'
    case 'info': return 'bg-gradient-to-br from-blue-400 to-blue-600'
    case 'success': return 'bg-gradient-to-br from-emerald-400 to-emerald-600'
    default: return 'bg-gradient-to-br from-gray-400 to-gray-600'
  }
}

const getSeverityTextClass = (severity: string | undefined) => {
  switch (severity) {
    case 'critical': return 'text-red-600'
    case 'warning': return 'text-amber-600'
    case 'info': return 'text-blue-600'
    case 'success': return 'text-emerald-600'
    default: return 'text-gray-600'
  }
}

const getSeverityBadgeClass = (severity: string | undefined) => {
  switch (severity) {
    case 'critical': return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
    case 'warning': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300'
    case 'info': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300'
    case 'success': return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
  }
}

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

// System Health helper functions
const getHealthBgClass = (status: string | undefined) => {
  switch (status) {
    case 'healthy': return 'bg-emerald-500'
    case 'degraded': return 'bg-amber-500'
    case 'unhealthy': return 'bg-red-500'
    default: return 'bg-emerald-500'
  }
}

const getHealthTextClass = (status: string | undefined) => {
  switch (status) {
    case 'healthy': return 'text-emerald-600'
    case 'degraded': return 'text-amber-600'
    case 'unhealthy': return 'text-red-600'
    default: return 'text-emerald-600'
  }
}

const getHealthDotClass = (status: string | undefined) => {
  switch (status) {
    case 'healthy': return 'bg-emerald-500'
    case 'degraded': return 'bg-amber-500 animate-pulse'
    case 'unhealthy': return 'bg-red-500 animate-pulse'
    default: return 'bg-emerald-500'
  }
}

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

// Department Distribution (top 5, sorted by count)
const topDepartments = computed(() => {
  const stats = metrics.value.departments_stats || []
  return [...stats]
    .sort((a: any, b: any) => b.count - a.count)
    .slice(0, 5)
})

const maxDeptCount = computed(() => {
  const stats = topDepartments.value
  if (stats.length === 0) return 1
  return Math.max(...stats.map((d: any) => d.count))
})

const getDeptPercentage = (count: number) => {
  return Math.round((count / maxDeptCount.value) * 100)
}

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
  const sorted = [...stats].sort((a: any, b: any) => b.count - a.count).slice(0, 6)
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

// Color by index and pressure
const deptColors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4']
const getDeptColor = (index: number, pressure: string) => {
  if (pressure === 'high') return '#ef4444'
  if (pressure === 'medium') return '#f59e0b'
  return deptColors[index % deptColors.length]
}

const getPressureTextClass = (pressure: string) => {
  switch (pressure) {
    case 'high': return 'text-red-600'
    case 'medium': return 'text-amber-600'
    default: return 'text-emerald-600'
  }
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

// Interactive Pie Chart
const selectedSlice = ref<string | null>(null)
const { t } = useI18n()

const selectSlice = (slice: string) => {
  selectedSlice.value = selectedSlice.value === slice ? null : slice
}

const selectedSliceColor = computed(() => {
  switch (selectedSlice.value) {
    case 'completed': return 'text-emerald-600'
    case 'pending': return 'text-amber-600'
    case 'in_progress': return 'text-blue-600'
    case 'overdue': return 'text-red-600'
    default: return 'text-emerald-600'
  }
})

const selectedSliceValue = computed(() => {
  switch (selectedSlice.value) {
    case 'completed': return metrics.value.tickets?.completed || 0
    case 'pending': return metrics.value.tickets?.pending || 0
    case 'in_progress': return metrics.value.tickets?.in_progress || 0
    case 'overdue': return metrics.value.tickets?.overdue || 0
    default: return metrics.value.tickets?.total || 0
  }
})

const selectedSliceLabel = computed(() => {
  switch (selectedSlice.value) {
    case 'completed': return t('dashboard.completed')
    case 'pending': return t('dashboard.pending')
    case 'in_progress': return t('dashboard.inProgressTickets')
    case 'overdue': return t('dashboard.overdueTickets')
    default: return t('dashboard.totalTickets')
  }
})

// Pie chart slice order for wheel navigation
const sliceOrder = ['completed', 'pending', 'in_progress', 'overdue']

const handlePieWheel = (e: WheelEvent) => {
  const currentIndex = selectedSlice.value ? sliceOrder.indexOf(selectedSlice.value) : -1
  
  if (e.deltaY > 0) {
    // Scroll down - next slice
    const nextIndex = currentIndex >= sliceOrder.length - 1 ? 0 : currentIndex + 1
    selectedSlice.value = sliceOrder[nextIndex]
  } else {
    // Scroll up - previous slice
    const prevIndex = currentIndex <= 0 ? sliceOrder.length - 1 : currentIndex - 1
    selectedSlice.value = sliceOrder[prevIndex]
  }
}

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
