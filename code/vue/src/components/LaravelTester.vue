<script setup>
import { ref, inject } from 'vue'

const axios = inject('axios')

const username = ref('a1@mail.pt')
const password = ref('123')
const responseData = ref('')

const submit = async () => {
    const responseLogin = await axios.post('/auth/login', {
        username: username.value,
        password: password.value
    })
    axios.defaults.headers.common.Authorization = "Bearer " + responseLogin.data.access_token
    const responseRequest = await axios.get('/categories')
    responseData.value = responseRequest.data.data[0].name

}
</script>

<template>
    <div class="my-5">
        <h2>Laravel Tester</h2>

        <form>
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" v-model="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" v-model="password">
            </div>
            <button @click.prevent="submit" type="submit" class="btn btn-primary">Submit</button>
            <div class="my-5" v-if="responseData">
                <label for="exampleFormControlTextarea1" class="form-label">Response</label>
                <textarea :value="responseData" class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
            </div>
        </form>
    </div>
</template>