<template>
  <van-pull-refresh v-model="isLoading" @refresh="onRefresh">
  <div class="home-wrap">
    <loading :loading="gLoading" :text="loadingText" />
    <van-nav-bar
        title="典来科技测试水果店"
        fixed
      ></van-nav-bar>
    <div>

    <!-- 未结算订单列表 -->
      <template v-if="list.length > 0">
        <van-cell-group class="orders"
          v-for="(item,i) in list"
          :key="item.orderid"
          @click.native="() => goDetail(i)">
          <van-cell  value="内容">
            <template class="lefttext">
              <!-- <span class="custom-text">时间戳:{{item.orderid}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> -->
              <span class="custom-text">订单数量:{{item.itemcount}}笔</span>
              <span class="custom-text">总金额:{{item.totalamount}}元&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </template>
            <van-icon slot="right-icon" name="search" class="custom-icon" style="line-height: inherit;"/>
          </van-cell>
        </van-cell-group>
      </template>
      <p class="nomore" v-else>当天没有未结算订单</p>
    </div>
  </div>
  <vm-back-top :bottom="70" :height="100" />
  </van-pull-refresh>
</template>

<script>
import {
  SwipeCell, Cell, CellGroup,
  Tag, Dialog, NavBar,
  Row, Col, Button, Icon, 
  Popup, List, Swipe,PullRefresh,
} from 'vant'
import { API } from '@/utils/api'
import Loading from '@/components/Loading'
export default {
  name: 'Order',
  components: {
    [Cell.name]: Cell,
    [CellGroup.name]: CellGroup,
    [SwipeCell.name]: SwipeCell,
    [Tag.name]: Tag,
    [NavBar.name]: NavBar,
    [Row.name]: Row,
    [Col.name]: Col,
    [Button.name]: Button,
    [Icon.name]: Icon,
    VanDialog: Dialog,
    [Popup.name]: Popup,
    [List.name]: List,
    [PullRefresh.name]: PullRefresh,
    Loading
  },
  data () {
    return {
      gLoading: false,
      loadingText: '',
      loading: false,
      mobile: '',
      nickname: '',
      list: [],
      name: '',
      pcbid: '',
      currentIdx: 0, // 当前编辑项
      isLoading: false,
    }
  },
  mounted () {
    let orders = JSON.parse(localStorage.getItem('orders'))
    let url =  location.href // //"http://balala.edianlai.com/index/client/client_login#/?pcbid=test"
    this.pcbid = url.substr(61)
    localStorage.setItem('pcbid',JSON.stringify(this.pcbid))
    if (orders) {
      this.list = orders
    } else {
      this.getData(this.pcbid)
    }
  },
  methods: {
    // 进入店铺详情页
    goDetail (i) {
      this.$router.push({ name: 'OrderItem', query: {index:i, total: this.list[i].totalamount} })
    },
    //获取未结算订单
    getData (pcbid) {
      this.loading = true
      let newlist=[]
      this.axios.post(API.getorders, { 'pcbid':pcbid }).then(data => {
        this.loading = false
        this.$set(this,'list',data)
        localStorage.setItem('orders', JSON.stringify(data))
      }).catch(e => {
        this.loading = false
      })
    },

    // 更新操作至localStorage
    update () {
      let user = JSON.parse(localStorage.getItem('user'))
      user.shopinfo = JSON.parse(JSON.stringify(this.list))
      localStorage.setItem('user', JSON.stringify(user))
    },

    onRefresh() {
      localStorage.clear()
      setTimeout(() => {
        this.$toast('刷新成功')
        this.getData(this.pcbid)
        this.isLoading = false
      }, 500);
    }
  }
}
</script>

<style scoped>
.orders{
  margin-top: 0.5rem;
}
.van-cell__title {
    text-align: left;
}
</style>

<style>
.home-wrap {
  background-color: #f2f2f2;
  min-height: 100vh;
  padding-top: 46px;
}
.home-wrap .van-nav-bar {
  background-color: #08979c;
  color: #fff;
}
</style>
