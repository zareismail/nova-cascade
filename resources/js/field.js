Nova.booting((Vue, router, store) => {
	// register belongs to field 
  Vue.component('index-cascade-belongs-to-field', Vue.component('index-belongs-to-field')) 
  Vue.component('detail-cascade-belongs-to-field', Vue.component('detail-belongs-to-field'))
  Vue.component('form-cascade-belongs-to-field', require('./components/BelongsTo/FormField'))  

  Vue.component('index-cascade-morph-to-field', Vue.component('index-morph-to-field')) 
  Vue.component('detail-cascade-morph-to-field', Vue.component('detail-morph-to-field'))
  Vue.component('form-cascade-morph-to-field', require('./components/MorphTo/FormField'))
})
