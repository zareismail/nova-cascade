<template>
  <form-morph-to-field v-if="creatingViaRelatedResource" :field="field" v-bind="$props" />
  <div v-else>
    <default-field
      :field="field"
      :show-errors="false"
      :field-name="fieldName"
      :show-help-text="field.helpText != null"
    >
      <select
        v-if="hasMorphToTypes"
        :disabled="isLocked || isReadonly"
        :data-testid="`${field.attribute}-type`"
        :dusk="`${field.attribute}-type`"
        slot="field"
        :value="resourceType"
        @change="handleSelectedMorphType"
        class="block w-full form-control form-input form-input-bordered form-select mb-3"
      >
        <option value="" selected :disabled="!field.nullable">
          {{ __('Choose Type') }}
        </option>

        <option
          v-for="option in field.morphToTypes"
          :key="option.value"
          :value="option.value"
          :selected="resourceType == option.value"
        >
          {{ option.singularLabel }}
        </option>
      </select>

      <label v-else slot="field" class="flex items-center select-none mt-3">
        {{ __('There are no available options for this resource.') }}
      </label>
    </default-field>

    <default-field
      :field="field"
      :errors="errors"
      :show-help-text="false"
      :field-name="fieldTypeName"
      v-if="hasMorphToTypes && resourceType"
    >
      <template slot="field"> 
        <cascade-morph-to 
          v-bind="$props" 
          v-for="(resource, index) in resources"
          :key="resource.key"
          :resource-type="resourceType"
          :queryResource="resource" 
          :relatedResource="relatedResource(index)"
          :relatedResourceId="relatedValue(index)"
          v-if="isVisible(index)"
          @change="updated(resource.key, $event)"
        />  
      </template>
    </default-field>
  </div>
</template>

<script>
import _ from 'lodash'
import storage from './../storage/MorphToFieldStorage'
import MorphTo from './MorphTo'
import {
  FormField, 
  HandlesValidationErrors,
} from 'laravel-nova'

export default {
  mixins: [ 
    HandlesValidationErrors,
    FormField,
  ],

  components: {
    'CascadeMorphTo': MorphTo
  },

  data: () => ({ 
    resourceType: '',
    selected: {}
  }),

  /**
   * Mount the component.
   */
  mounted() {  
    this.resourceType = this.field.morphToType || ''
    this.field.fill = this.fill
  },

  methods: {
    /**
     * Fill the forms formData with details from this field
     */
    fill(formData) {    
      formData.append(this.field.attribute, this.viaResourceId)
      formData.append(this.field.attribute + '_type', this.viaResource)
    },

    handleSelectedMorphType(event) { 
      this.resourceType = event.target.value
    },

    updated(resource, value) {     
      this.$set(this.selected, this.selectionKey(resource), value)
    }, 

    relatedResource(index) { 
      return this.resources[index - 1] || {};
    }, 

    relatedValue(index) {   
      var related = this.relatedResource(index)  

      return this.selected[this.selectionKey(related.key)] || ''
    },

    selectionKey(resource){
      return this.resourceType+'.'+resource
    },

    isVisible(index) { 
      return index == 0 || this.relatedValue(index) 
    }, 
  },

  computed: {
    selectedResourceId() {
      return this.selected[this.selectionKey(this.field.resourceName)]
    },

    /**
     * Determine if the field is locked
     */
    isLocked() {
      return Boolean(this.viaResource && this.field.reverse)
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

    resources() {
      var resources = this.field.resources[this.resourceType] || []

      return resources.map(resource => {
        if(this.selected[this.selectionKey(resource.key)]) {
          resource.value = this.selected[this.selectionKey(resource.key)]
        }  

        return resource
      })
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
     * Return the morphable type label for the field
     */
    fieldName() {
      return this.field.name
    },


    /**
     * Return the morphable type label for the field
     */
    targetField() {
      return this.resources.pop()
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

  },
}
</script>
