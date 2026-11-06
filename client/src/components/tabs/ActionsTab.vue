<template>
  <div class="actions-tab-content">
    <div v-if="actionsStore.loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate size="40" />
    </div>

    <div v-else-if="parcelActions.length === 0" class="empty-state">
      <v-icon icon="mdi-lightbulb-outline" size="48" color="grey-lighten-2" class="mb-3" />
      <div class="text-body-1 text-medium-emphasis">No actions recorded for this parcel yet</div>
    </div>

    <div v-else>
      <ActionRowCard
        v-for="action in parcelActions"
        :key="action.id"
        :action="action"
        :selected="selectedActions.includes(action.id)"
        :expanded="expandedActions.includes(action.id)"
        @update:selected="handleActionSelect(action.id, $event)"
        @toggle-expand="handleToggleExpand(action.id)"
        @edit="handleEditAction"
        @delete="handleDeleteAction"
      />
    </div>

    <AddActionModal
      v-model="showEditModal"
      :action="editingAction"
      @save="handleSaveAction"
      @cancel="showEditModal = false; editingAction = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useActionsStore } from '../../stores/actions'
import type { Action } from '../../stores/actions'
import type { Parcel } from '../../stores/parcels'
import ActionRowCard from '../actions/ActionRowCard.vue'
import AddActionModal from '../actions/AddActionModal.vue'

const props = defineProps<{
  parcel: Parcel
}>()

const actionsStore = useActionsStore()

const parcelActions = computed(() => actionsStore.getByParcel(props.parcel.id))

const selectedActions = ref<string[]>([])
const expandedActions = ref<string[]>([])
const showEditModal = ref(false)
const editingAction = ref<Action | null>(null)

const handleActionSelect = (actionId: string, selected: boolean) => {
  if (selected) {
    selectedActions.value.push(actionId)
  } else {
    selectedActions.value = selectedActions.value.filter(id => id !== actionId)
  }
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
  showEditModal.value = true
}

const handleDeleteAction = async (actionId: string) => {
  await actionsStore.remove(actionId)
  selectedActions.value = selectedActions.value.filter(id => id !== actionId)
}

const handleSaveAction = async () => {
  await actionsStore.refresh(props.parcel.id, props.parcel.name)
  showEditModal.value = false
  editingAction.value = null
}

onMounted(async () => {
  await actionsStore.load(props.parcel.id, props.parcel.name)
})
</script>

<style scoped>
.actions-tab-content {
  padding: 16px;
}

.empty-state {
  text-align: center;
  padding: 48px 16px;
}
</style>
