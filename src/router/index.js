import Vue from 'vue'
import Router from 'vue-router'

const Order = () => import(/* webpackChunkName: "order" */'@/pages/order')
const OrderItem = () => import(/* webpackChunkName: "login" */'@/pages/orderitem')
Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Root',
      component: Order
    },
    {
      path: '/orderitem',
      name: 'OrderItem',
      component: OrderItem,
    }
  ]
})
