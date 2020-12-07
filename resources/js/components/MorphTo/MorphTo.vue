<template> 
  <div>
    <div class="flex items-center mb-3">
      <search-input
        class="w-full"
        v-if="isSearchable && !isLocked && !isReadonly"
        :data-testid="`${field.attribute}-search-input`"
        :disabled="!resourceType || isLocked || isReadonly"
        @input="performSearch"
        @clear="clearSelection"
        @selected="selectResource"
        :debounce="field.debounce"
        :value="selectedResource"
        :data="availableResources"
        :clearable="field.nullable"
        trackBy="value"
      >
        <div
          slot="default"
          v-if="selectedResource"
          class="flex items-center"
        >
          <div v-if="selectedResource.avatar" class="mr-3">
            <img
              :src="selectedResource.avatar"
              class="w-8 h-8 rounded-full block"
            />
          </div>

          {{ selectedResource.display }}
        </div>
        <span v-else slot="default">{{ placeholder }}</span>

        <div
          slot="option"
          slot-scope="{ option, selected }"
          class="flex items-center"
        >
          <div v-if="option.avatar" class="mr-3">
            <img :src="option.avatar" class="w-8 h-8 rounded-full block" />
          </div>

          <div>
            <div
              class="text-sm font-semibold leading-5 text-90"
              :class="{ 'text-white': selected }"
            >
              {{ option.display }}
            </div>

            <div
              v-if="field.withSubtitles"
              class="mt-1 text-xs font-semibold leading-5 text-80"
              :class="{ 'text-white': selected }"
            >
              <span v-if="option.subtitle">{{ option.subtitle }}</span>
              <span v-else>{{ __('No additional information...') }}</span>
            </div>
          </div>
        </div>
      </search-input>

      <select-control
        v-if="!isSearchable || isLocked"
        class="form-control form-select w-full"
        :class="{ 'border-danger': hasError }"
        :dusk="`${field.attribute}-select`"
        @change="selectResourceFromSelectControl"
        :disabled="!resourceType || isLocked || isReadonly"
        :options="availableResources"
        :selected="selectedResourceId"
        label="display"
      >
        <option
          value=""
          :disabled="!field.nullable"
          :selected="selectedResourceId == ''"
        >
          {{ __('Choose') }} {{ fieldTypeName }}
        </option>
      </select-control>

      <create-relation-button
        v-if="canShowNewRelationModal"
        @click="openRelationModal"
        class="ml-1"
        :dusk="`${field.attribute}-inline-create`"
      />
    </div>

    <portal to="modals" transition="fade-transition">
      <create-relation-modal
        v-if="relationModalOpen && !shownViaNewRelationModal"
        @set-resource="handleSetResource"
        @cancelled-create="closeRelationModal"
        :resource-name="resourceType"
        :via-relationship="viaRelationship"
        :via-resource="viaResource"
        :via-resource-id="viaResourceId"
        width="800"
      />
    </portal>

    <!-- Trashed State -->
    <div v-if="shouldShowTrashed">
      <checkbox-with-label
        :dusk="field.attribute + '-with-trashed-checkbox'"
        :checked="withTrashed"
        @input="toggleWithTrashed"
      >
        {{ __('With Trashed') }}
      </checkbox-with-label>
    </div> 
  </div>
</template>

<script>
import _ from 'lodash'
import storage from './../storage/MorphToFieldStorage'
import {
  FormField,
  PerformsSearches,
  TogglesTrashed,
  HandlesValidationErrors,
} from 'laravel-nova'

export default {
  mixins: [
    PerformsSearches,
    TogglesTrashed,
    HandlesValidationErrors,
    FormField,
  ],

  props: {
    resourceId: {},
    queryResource: {
      type: Object,
      required: true,
    },
    relatedResource: {
      type: Object,
      default: null,
    },
    relatedResourceId: {
      type: Number,
      default:null,
    },
    resourceType: null
  },

  data: () => ({ 
    initializingWithExistingResource: false,
    softDeletes: false,
    selectedResourceId: null,
    selectedResource: null,
    search: '',
    relationModalOpen: false,
    withTrashed: false,
  }),

  /**
   * Mount the component.
   */
  mounted() {
    this.selectedResourceId = this.queryResource.value

    if (this.editingExistingResource) {
      this.initializingWithExistingResource = true
      this.selectedResourceId = this.queryResource.value
    } else if (this.creatingViaRelatedResource) {
      this.initializingWithExistingResource = true
      this.resourceType = this.viaResource
      this.selectedResourceId = this.viaResourceId
    }

    if (this.shouldSelectInitialResource) {
      if (!this.resourceType && this.field.defaultResource) {
        this.resourceType = this.field.defaultResource
      }
      this.getAvailableResources().then(() => this.selectInitialResource())
    }

    if (this.resourceType) {
      this.determineIfSoftDeletes()
    } 

    this.handleChange()
  },

  methods: {
    /**
     * Select a resource using the <select> control
     */
    selectResourceFromSelectControl(e) {
      this.selectedResourceId = e.target.value
      this.selectInitialResource()
      this.handleChange()
    }, 

    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {  
      if (this.selectedResourceId && this.resourceType) {
        formData.append(this.field.attribute, this.selectedResourceId)
        formData.append(this.field.attribute + '_type', this.resourceType)
      } else {
        formData.append(this.field.attribute, '')
        formData.append(this.field.attribute + '_type', '')
      }

      formData.append(this.field.attribute + '_trashed', this.withTrashed)
    }, 

    /**
     * Get the resources that may be related to this resource.
     */
    getAvailableResources(search = '') {
      return storage
        .fetchAvailableResources(
          this.resourceName,
          this.field.attribute,
          this.queryParams
        )
        .then(({ data: { resources, softDeletes, withTrashed } }) => {
          if (this.initializingWithExistingResource || !this.isSearchable) {
            this.withTrashed = withTrashed
          }

          this.initializingWithExistingResource = false
          this.availableResources = resources
          this.softDeletes = softDeletes
        })
    },

    /**
     * Select the initial selected resource
     */
    selectInitialResource() {
      this.selectedResource = _.find(
        this.availableResources,
        r => r.value == this.selectedResourceId
      )
    },

    /**
     * Determine if the selected resource type is soft deleting.
     */
    determineIfSoftDeletes() {
      return storage
        .determineIfSoftDeletes(this.resourceType)
        .then(({ data: { softDeletes } }) => (this.softDeletes = softDeletes))
    },

    /**
     * Handle the changing of the resource type.
     */
    async refreshResourcesForTypeChange(type) {
      this.resourceType = type
      this.availableResources = []
      this.selectedResource = ''
      this.selectedResourceId = ''
      this.withTrashed = false

      // if (this.resourceType == '') {
      this.softDeletes = false
      // } else if (this.field.searchable) {
      this.determineIfSoftDeletes()
      // }

      if (!this.isSearchable && this.resourceType) {
        this.getAvailableResources()
      }

      this.field.fill = this.fill

      this.handleChange()
    },

    /**
     * Toggle the trashed state of the search
     */
    toggleWithTrashed() {
      this.withTrashed = !this.withTrashed

      // Reload the data if the component doesn't support searching
      if (!this.isSearchable) {
        this.getAvailableResources()
      }
    },

    openRelationModal() {
      this.relationModalOpen = true
    },

    closeRelationModal() {
      this.relationModalOpen = false
    },

    handleSetResource({ id }) {
      this.closeRelationModal()
      this.selectedResourceId = id
      this.getAvailableResources().then(() => this.selectInitialResource())
    },

    /**
     * Update the field's internal value
     */
    handleChange(event) {
      this.$emit('change', this.selectedResourceId)
    }, 
  },

  computed: {
    /**
     * Determine if an existing resource is being updated.
     */
    editingExistingResource() {
      return Boolean(this.field.morphToId && this.field.morphToType)
    },

    /**
     * Determine if we are creating a new resource via a parent relation
     */
    creatingViaRelatedResource() {
      return Boolean(
        _.find(
          this.field.morphToTypes,
          type => type.value == this.viaResource
        ) &&
          this.viaResource &&
          this.viaResourceId
      )
    },

    /**
     * Determine if we should select an initial resource when mounting this field
     */
    shouldSelectInitialResource() {
      return Boolean(
        this.editingExistingResource ||
          this.creatingViaRelatedResource ||
          Boolean(this.queryResource.value && this.field.defaultResource)
      )
    },

    /**
     * Determine if the related resources is searchable
     */
    isSearchable() {
      return Boolean(this.field.searchable)
    },

    shouldLoadFirstResource() {
      return (
        this.isSearchable &&
        this.shouldSelectInitialResource &&
        this.initializingWithExistingResource
      )
    },

    /**
     * Get the query params for getting available resources
     */
    queryParams() {
      return {
        params: {
          type: this.resourceType,
          current: this.selectedResourceId,
          first: this.shouldLoadFirstResource,
          search: this.search,
          withTrashed: this.withTrashed,
          viaResource: this.viaResource,
          viaResourceId: this.viaResourceId,
          viaRelationship: this.viaRelationship,
          queryResource: this.queryResource.key, 
          relatedResourceId: this.relatedResourceId,
        },
      }
    },

    /**
     * Determine if the field is locked
     */
    isLocked() {
      return Boolean(this.viaResource && this.field.reverse)
    },

    /**
     * Return the morphable type label for the field
     */
    fieldName() {
      return this.field.name
    },

    /**
     * Return the selected morphable type's label
     */
    fieldTypeName() {
      if (this.resourceType) {
        return _.find(this.field.morphToTypes, type => {
          return type.value == this.resourceType
        }).singularLabel
      }

      return ''
    },

    /**
     * Determine if the field is set to readonly.
     */
    isReadonly() {
      return (
        this.field.readonly || _.get(this.field, 'extraAttributes.readonly')
      )
    },

    /**
     * Determine whether there are any morph to types.
     */
    hasMorphToTypes() {
      return this.field.morphToTypes.length > 0
    },

    authorizedToCreate() {
      return _.find(Nova.config.resources, resource => {
        return resource.uriKey == this.resourceType
      }).authorizedToCreate
    },

    canShowNewRelationModal() {
      return (
        this.field.showCreateRelationButton &&
        this.resourceType &&
        !this.shownViaNewRelationModal &&
        !this.isLocked &&
        !this.isReadonly &&
        this.authorizedToCreate
      )
    },

    shouldShowTrashed() {
      return (
        this.softDeletes &&
        !this.isLocked &&
        !this.isReadonly &&
        this.field.displaysWithTrashed
      )
    },

    /**
     * Return the placeholder text for the field.
     */
    placeholder() {
      if(this.queryResource) {
        return this.__('Choose :resource', {resource : this.queryResource['label']})
      }

      return this.field.placeholder || this.__('—') 
    },
  },

  watch: {
    resourceType(type) {
      this.refreshResourcesForTypeChange(type)
    },

    relatedResourceId: function() {
      this.refreshResourcesForTypeChange(this.resourceType) 
    },

    selectedResource: function(selected) {  
      this.selectedResourceId = selected ? selected.value : null
      this.handleChange()
    }
  }
}
</script>
