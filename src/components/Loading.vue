<template>
  <div class="g-loading" v-show="loading">
    <van-loading color="white" v-show="loading" />
    <p v-if="text">{{text}}</p>
  </div>
</template>

<script>
import { Loading } from 'vant'
export default {
  name: 'Loading',
  props: {
    loading: Boolean,
    text: String
  },
  components: {
    [Loading.name]: Loading
  }
}
</script>

<style scoped>
.g-loading {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  padding: 10px 15px;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  border-radius: 4Px;
  z-index: 2100;
}
p {
  margin-top: 10px;
}
</style>

<style>
.g-loading .van-loading {
  display: inline-block;
}
</style>
