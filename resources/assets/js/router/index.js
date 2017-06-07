import Vue from 'vue'
import VueRouter from 'vue-router'

import Register from '../views/Auth/Register'
import Login from '../views/Auth/Login'
import RecipeIndex from '../views/recipes/Index.vue'
import RecipeShow from '../views/recipes/Show.vue'
import RecipeForm from '../views/recipes/Form.vue'

Vue.use(VueRouter)

const router = new VueRouter({
	routes: [
		{ path: '/', component: RecipeIndex},
		{ path: '/recipes/create', component: RecipeForm, meta: { mode: 'create' }},
		{ path: '/recipes/:id', component: RecipeShow},
		{ path: '/recipes/:id/edit', component: RecipeForm, meta: { mode: 'edit' }},
		{ path: '/register', component: Register },
		{ path: '/login', component: Login}
	]
})

export default router