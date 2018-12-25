// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import axios from './request'
import 'lib-flexible'
import { Toast } from 'vant'
import VmBackTop from 'vue-multiple-back-top'

Vue.use(Toast)
Vue.component(VmBackTop.name, VmBackTop)
Vue.config.productionTip = false
Vue.prototype.axios = axios
/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  components: { App },
  template: '<App/>'
})
