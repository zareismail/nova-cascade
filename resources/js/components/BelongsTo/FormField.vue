<template>
  <form-belongs-to-field v-if="creatingViaRelatedResource" v-bind="$props" />
  <default-field v-else  :field="field" :errors="errors" :show-help-text="showHelpText"> 
    <template slot="field">
      <cascade-belongs-to 
        v-bind="$props" 
        v-for="(resource, index) in resources"
        :key="resource.key"
        :queryResource="resource" 
        :relatedResource="relatedResource(index)"
        :relatedResourceId="relatedValue(index)"
        v-if="isVisible(index)"
        @change="updated(resource.key, $event)"
      />  
    </template>
  </default-field> 
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import BelongsTo from './BelongsTo.vue'

export default {
  mixins: [FormField, HandlesValidationErrors],

  components: {
    'CascadeBelongsTo': BelongsTo
  },

  data: () => ({
    selected: {}, 
  }), 

  mounted() { 
    this.field.fill = this.fill 
  },

  props: ['resourceName', 'resourceId', 'field'],

  methods: {
    /*
     * Set the initial, internal value for the field.
     */
    setInitialValue() {
      this.value = this.field.value || '' 
    },

    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      formData.append(this.field.attribute, this.selected[this.field.resourceName] || null)
    },

    updated(resource, value) {  
      this.$set(this.selected, resource, parseInt(value))  
    },

    relatedResource(index) { 
      return this.field.resources[index - 1] || {};
    }, 

    relatedValue(index) { 
      var related = this.relatedResource(index) 

      return this.selected[related.key] || null
    },

    isVisible(index) { 
      return index == 0 || this.relatedValue(index) 
    }, 
  },

  computed: { 
    resources() {
      return this.field.resources.map(resource => {
        if(this.selected[resource.key]) {
          resource.value = this.selected[resource.key]
        }  

        return resource
      })
    },

    /**
     * Determine if we are creating a new resource via a parent relation
     */
    creatingViaRelatedResource() {
      return (
        this.viaResource == this.field.resourceName &&
        this.field.reverse &&
        this.viaResourceId
      )
    },
  }
}
</script>
