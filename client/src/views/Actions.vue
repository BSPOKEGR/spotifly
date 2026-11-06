<template>
  <v-app>
    <AppHeader />

    <v-main>
      <div class="actions-page">
        <!-- Filters Header -->
        <FiltersHeader
          :filters="filters"
          :parcels="parcelsStore.parcels"
          @update:filters="updateFilters"
          @add-action="showAddModal = true"
        />

        <!-- Bulk Controls Row -->
        <BulkControlsRow
          :selected-count="selectedActions.length"
          :total-count="paginatedActions.length"
          :all-expanded="allExpanded"
          @select-all="handleSelectAll"
          @expand-collapse-all="handleExpandCollapseAll"
          @delete-selected="handleDeleteSelected"
          @update:items-per-page="itemsPerPage = $event"
          @update:page="currentPage = $event"
          :items-per-page="itemsPerPage"
          :current-page="currentPage"
          :total-pages="totalPages"
        />

        <!-- Loading State -->
        <div v-if="loading" class="d-flex justify-center align-center pa-8">
          <v-progress-circular indeterminate size="48" />
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="d-flex justify-center align-center pa-8">
          <v-alert type="error" :text="error" />
        </div>

        <!-- Actions List -->
        <div v-else class="actions-list">
          <ActionRowCard
            v-for="action in paginatedActions"
            :key="action.id"
            :action="action"
            :selected="selectedActions.includes(action.id)"
            :expanded="expandedActions.includes(action.id)"
            @update:selected="handleActionSelect(action.id, $event)"
            @toggle-expand="handleToggleExpand(action.id)"
            @edit="handleEditAction"
            @delete="handleDeleteAction"
          />

          <!-- Empty State -->
          <div v-if="filteredActions.length === 0" class="empty-state">
            <v-icon icon="mdi-lightbulb" size="64" color="grey-lighten-2" class="mb-4" />
            <h3 class="text-h6 text-medium-emphasis mb-2">No actions found</h3>
            <p class="text-body-2 text-medium-emphasis">
              Try adjusting your filters or add a new action
            </p>
          </div>
        </div>
      </div>
    </v-main>

    <!-- Add/Edit Action Modal -->
    <AddActionModal
      v-model="showAddModal"
      :action="editingAction"
      @save="handleSaveAction"
      @cancel="handleCancelModal"
    />
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useActionsStore } from '../stores/actions'
import { useParcelsStore } from '../stores/parcels'
import type { Action } from '../stores/actions'
import AppHeader from '../components/AppHeader.vue'
import FiltersHeader from '../components/actions/FiltersHeader.vue'
import BulkControlsRow from '../components/actions/BulkControlsRow.vue'
import ActionRowCard from '../components/actions/ActionRowCard.vue'
import AddActionModal from '../components/actions/AddActionModal.vue'

const authStore = useAuthStore()
const actionsStore = useActionsStore()
const parcelsStore = useParcelsStore()

const showAddModal = ref(false)
const editingAction = ref<Action | null>(null)
const selectedActions = ref<string[]>([])
const expandedActions = ref<string[]>([])
const allExpanded = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(10)

const loading = computed(() => actionsStore.loading)
const error = computed(() => actionsStore.error)

const filters = ref({
  actionType: '',
  parcelId: '',
  dateRange: { start: '', end: '' }
})

const toDateString = (val: any): string => {
  if (!val) return ''
  if (val instanceof Date) {
    const y = val.getFullYear()
    const m = String(val.getMonth() + 1).padStart(2, '0')
    const d = String(val.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
  }
  return String(val).slice(0, 10)
}

const filteredActions = computed(() => {
  const startStr = toDateString(filters.value.dateRange.start)
  const endStr = toDateString(filters.value.dateRange.end)
  return actionsStore.actions.filter(action => {
    if (filters.value.actionType && action.type !== filters.value.actionType) {
      return false
    }
    const actionDate = toDateString(action.date)
    if (startStr && actionDate < startStr) {
      return false
    }
    if (endStr && actionDate > endStr) {
      return false
    }
    return true
  })
})

watch(() => filters.value.parcelId, async (parcelId) => {
  const parcelName = parcelsStore.parcels.find(p => p.id === parcelId)?.name
  await actionsStore.load(parcelId || undefined, parcelName)
  currentPage.value = 1
  selectedActions.value = []
})

const totalPages = computed(() => {
  return Math.ceil(filteredActions.value.length / itemsPerPage.value)
})

const paginatedActions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredActions.value.slice(start, end)
})

const updateFilters = (newFilters: any) => {
  filters.value = { ...filters.value, ...newFilters }
  currentPage.value = 1
}

const handleSelectAll = (selected: boolean) => {
  const pageIds = paginatedActions.value.map(action => action.id)
  if (selected) {
    const existing = new Set(selectedActions.value)
    pageIds.forEach(id => existing.add(id))
    selectedActions.value = Array.from(existing)
  } else {
    const pageIdSet = new Set(pageIds)
    selectedActions.value = selectedActions.value.filter(id => !pageIdSet.has(id))
  }
}

const handleActionSelect = (actionId: string, selected: boolean) => {
  if (selected) {
    selectedActions.value.push(actionId)
  } else {
    selectedActions.value = selectedActions.value.filter(id => id !== actionId)
  }
}

const handleExpandCollapseAll = () => {
  if (allExpanded.value) {
    expandedActions.value = []
  } else {
    expandedActions.value = paginatedActions.value.map(action => action.id)
  }
  allExpanded.value = !allExpanded.value
}

const handleToggleExpand = (actionId: string) => {
  const index = expandedActions.value.indexOf(actionId)
  if (index > -1) {
    expandedActions.value.splice(index, 1)
  } else {
    expandedActions.value.push(actionId)
  }
}

const handleEditAction = (action: Action) => {
  editingAction.value = action
  showAddModal.value = true
}

const handleDeleteAction = async (actionId: string) => {
  try {
    await actionsStore.remove(actionId)
    selectedActions.value = selectedActions.value.filter(id => id !== actionId)
  } catch (e: any) {
    console.error(e)
  }
}

const handleDeleteSelected = async () => {
  try {
    await actionsStore.removeMany(selectedActions.value)
    selectedActions.value = []
  } catch (e: any) {
    console.error(e)
  }
}

const handleSaveAction = async (_savedActions: any[]) => {
  await actionsStore.refresh()
  showAddModal.value = false
  editingAction.value = null
}

const handleCancelModal = () => {
  showAddModal.value = false
  editingAction.value = null
}

onMounted(async () => {
  authStore.initAuth()
  await parcelsStore.loadParcels()
  await actionsStore.load()
})
</script>

<style scoped>
.actions-page {
  background-color: #f5f5f5;
  min-height: calc(100vh - 64px);
}

.actions-list {
  padding: 16px;
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

.empty-state {
  text-align: center;
  padding: 64px 16px;
}

.actions-list::-webkit-scrollbar {
  width: 4px;
}

.actions-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.actions-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.actions-list::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
