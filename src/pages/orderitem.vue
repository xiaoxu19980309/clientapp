<template>
  <div class="home-wrap">
    <loading :loading="gLoading" :text="loadingText" />
    <van-nav-bar
        title="订单详情"
        fixed
        left-arrow
        left-text="返回"
        @click-left="back"
      />

    <!-- 未结算订单列表 -->
    <div>
      <template v-if="goodlist.length > 0">
        <van-cell-group 
          v-for="(item) in goodlist"
          :key="item.id"
          >
          <van-cell>
            <span>
              订单编号：{{orderid}}
            </span>
          </van-cell>
          <van-cell >
            <template class="lefttext">
              <span class="custom-text">交易时间:{{item.intime}}</span><br>
              <span class="custom-text">品名&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;数量&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;单价(元/kg)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;金额(元)</span><br>
              <span class="custom-text">
                {{item.name}}&nbsp;&nbsp;&nbsp;&nbsp;{{item.number}}kg&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                {{item.price}}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{item.itemamount}}
              </span><br>
            </template>
          </van-cell>
          <van-cell>
            <span>
              合计：{{sum}}元
            </span>
          </van-cell>
        </van-cell-group>
      </template>
    </div>

    <van-row class="bottom-box line-top">
        <!-- <van-button bottom-action @click.native="pay">点击支付</van-button> -->
        <form action="http://balala.edianlai.com/index/alipay/pay.html" method="post" target="_blank">
          <input name="no" :value="orderid" style="display:none;"/>
          <input name="price" :value="sum" style="display:none;"/>
          <button class="new-btn-login" type="submit">点击支付</button>
        </form>
    </van-row>

  </div>
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
      index: 0,
      list: [],
      goodlist: [],
      currentIdx: 0, // 当前编辑项
      isLoading: false,
      sum: 0,
      orderid: 0,
    }
  },
  mounted () {
    let { index,total } = this.$route.query
    let orders = JSON.parse(localStorage.getItem('orders'))
    this.index = index
    this.sum = total
    let pcbid = JSON.parse(localStorage.getItem('shopid'))
    if (orders) {
      this.list = orders
    } else {
      this.getData(pcbid)
    }
    this.goodlist = this.list[index].goods
    this.orderid = this.list[index].orderid
  },
  methods: {
    back(){
      this.$router.go(-1)
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

    //支付
    pay() {
      var price = this.sum
      var no = this.orderid
      window.open('./../alipay/pay.html?price='+price)
    },

    //刷新
    onRefresh() {
      setTimeout(() => {
        this.$toast('刷新成功');
        this.isLoading = false;
      }, 500);
    }
  }
}
</script>

<style scoped>
.home-wrap {
  background-color: #f2f2f2;
  min-height: 100vh;
  padding-top: 46px;
}
.bottom-box {
  position: fixed;
  bottom: 0;
  width: 100%;
}
.new-btn-login {
    width: 100%;
    height: 50px;
    line-height: 50px;
    border: 0;
    border-radius: 0;
    font-size: 16px;
    color: #fff;
    background-color: #ff976a;
}
</style>

<style>
.home-wrap .van-nav-bar {
  background-color: #08979c;
  color: #fff;
}
.van-nav-bar .van-icon {
    color: white;
}
.van-nav-bar__text {
    color: white;
}
</style>
