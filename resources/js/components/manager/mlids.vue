<template>
  <div>
    <v-row>
      <v-col cols="12">
        <v-row>
          <v-col cols="3">
            <v-card-title>
              <v-text-field
                v-model="search"
                append-icon="mdi-magnify"
                label="Поиск"
                outlined
                rounded
              ></v-text-field>
            </v-card-title>
          </v-col>
          <v-col cols="3">
            Фильтр по поставщикам
            <v-select
              v-model="filterProviders"
              :items="providers"
              item-text="name"
              item-value="id"
              outlined
              rounded
            ></v-select>
          </v-col>
          <v-col cols="3">
            Первые цифры номера

            <v-text-field
              v-model.lazy.trim="filtertel"
              append-icon="mdi-phone"
              outlined
              rounded
            ></v-text-field>
          </v-col>
          <v-col cols="3">
            Фильтр по статусу
            <v-select
              v-model="filterStatus"
              :items="filterstatuses"
              item-text="name"
              item-value="id"
              outlined
              rounded
            >
              <template v-slot:selection="{ item }">
                <i
                  :style="{
                    background: item.color,
                    outline: '1px solid grey',
                  }"
                  class="sel_stat mr-4"
                ></i
                >{{ item.name }}
              </template>
              <template v-slot:item="{ item }">
                <i
                  :style="{
                    background: item.color,
                    outline: '1px solid grey',
                  }"
                  class="sel_stat mr-4"
                ></i
                >{{ item.name }}
              </template>
            </v-select>
          </v-col>
        </v-row>

        <v-row>
          <v-col>
            <v-card :class="{ 'd-none': todayItems.length == 0 }">
              <!-- @click:row="clickrow"
              :expanded="expanded" show-expand  hide-default-header   show-select -->
              <v-data-table
                id="ontime"
                v-model.lazy.trim="selected"
                :headers="headerstime"
                item-key="id"
                :single-select="true"
                :single-expand="true"
                :items="todayItems"
                ref="todaytable"
                @click:row="clickrow"
                :items-per-page="100"
                hide-default-footer
              >
                <template v-slot:item.tel="{ item }">
                  <a
                    class="tel"
                    :href="'sip:' + item.tel"
                    @click.stop="
                      qtytel(item.id);
                      lid_id = item.id;
                    "
                  >
                    {{ item.tel }}
                  </a>
                  <span @click.prevent.stop="wp_call(item)">
                    <!-- :class="{ active: active_el == item.id }" -->
                    <v-icon small> mdi-headset </v-icon>
                  </span>
                </template>

                <template v-slot:item.status="{ item }">
                  <div class="status_wrp" @click.stop="openDialog(item)">
                    <b
                      :style="{
                        background: stylecolor(item.status_id),
                        outline: '#999 solid 1px',
                      }"
                    ></b>
                    <span>{{ item.status }}</span>
                  </div>
                </template>
                <template v-slot:item.text="{ item }">
                  <span class="fixwidth" :title="item.text">{{
                    item.text
                  }}</span>
                </template>
                <template v-slot:item.address="{ item }">
                  <v-btn
                    small
                    class="teal lighten-4"
                    @click.stop="copyTo(item.address)"
                    v-if="item.address"
                    >{{ item.address }}</v-btn
                  >
                </template>
                <template v-slot:item.actions="{ item }">
                  <v-icon small @click.stop="deleteTime(item)">
                    mdi-delete
                  </v-icon>
                </template>
                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <v-row>
                      <v-col cols="12">
                        <logtel :lid_id="lid_id" :key="lid_id" />
                      </v-col>
                    </v-row>
                  </td>
                </template>
              </v-data-table>
            </v-card>
            <v-card>
              <!-- show-expand show-select  :expanded="expanded"-->
              <v-data-table
                id="maintable"
                v-model.lazy.trim="selected"
                :headers="headers"
                item-key="id"
                :search="search"
                :single-select="true"
                :single-expand="true"
                :items="filteredItems"
                ref="datatable"
                @click:row="clickrow"
                :footer-props="{
                  'items-per-page-options': [10, 50, 100, 250, 500, -1],
                  'items-per-page-text': '',
                }"
              >
                <template
                  v-slot:top="{ pagination, options, updateOptions }"
                  :footer-props="{
                    'items-per-page-options': [10, 50, 100, 250, 500, -1],
                    'items-per-page-text': '',
                  }"
                >
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[10, 50, 100, 250, 500, -1]"
                    :items-per-page-text="''"
                  />
                </template>

                <template v-slot:item.tel="{ item }">
                  <a
                    class="tel"
                    :href="'sip:' + item.tel"
                    @click.stop="
                      qtytel(item.id);
                      lid_id = item.id;
                    "
                  >
                    {{ item.tel }}
                  </a>
                  <span @click.prevent.stop="wp_call(item)">
                    <!-- :class="{ active: active_el == item.id }" -->
                    <v-icon small> mdi-headset </v-icon>
                  </span>
                </template>

                <template v-slot:item.status="{ item }">
                  <div class="status_wrp mx-1" @click.stop="openDialog(item)">
                    <b
                      :style="{
                        background: stylecolor(item.status_id),
                        outline: '#999 solid 1px',
                      }"
                    ></b>
                    <span>{{ item.status }}</span>
                  </div>
                </template>
                <template v-slot:item.text="{ item }">
                  <span class="fixwidth" :title="item.text">{{
                    item.text
                  }}</span>
                </template>
                <template v-slot:item.address="{ item }">
                  <v-btn
                    small
                    class="teal lighten-4"
                    @click.stop="copyTo(item.address)"
                    v-if="item.address"
                    >{{ item.address }}</v-btn
                  >
                </template>
                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <v-row>
                      <v-col cols="12">
                        <logtel :lid_id="lid_id" :key="componentKey" />
                      </v-col>
                    </v-row>
                  </td>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
      </v-col>
    </v-row>

    <v-snackbar
      v-model="snackbar"
      top
      rigth
      timeout="6000"
      color="success"
      dark
    >
      {{ message }}
      <template v-slot:action="{ attrs }">
        <v-btn color="white" text v-bind="attrs" @click="snackbar = false">
          Х
        </v-btn>
      </template>
    </v-snackbar>
    <v-dialog v-model="dial" persistent max-width="600px">
      <v-card rounded class="rounded-xl pa-4">
        <v-card-title class="text-h5">
          <!-- @change="putSelectedLidsDB" -->
          <div class="wrp__statuses">
            <template v-for="(status, ikey) in statuses">
              <input
                type="radio"
                :value="status.id"
                :id="'st' + status.id"
                v-model="selectedStatus"
                style="display: none"
                :key="'i' + ikey"
              />
              <label
                :for="'st' + status.id"
                class="status_wrp v-label"
                :key="'l' + ikey"
                :class="{ hideStatus: hideStatus(status.id) }"
              >
                <b
                  :style="{
                    background: status.color,
                    outline: '1px solid grey',
                  }"
                ></b>
                <span>{{ status.name }}</span>
              </label>
            </template>
          </div>
        </v-card-title>

        <v-card-text>
          Сообщение
          <v-textarea
            class="px-2 border mb-4"
            rows="1"
            v-model="text_message"
            :value="text_message"
          ></v-textarea>
          <v-row>
            <v-col v-if="selectedStatus == 10">
              Сумма депозита*
              <v-text-field
                required
                v-model="depozit_val"
                class="border px-2 mb-4"
                @keypress="filter()"
                prepend-inner-icon="mdi-currency-usd"
              ></v-text-field
            ></v-col>
            <v-col class="pt-9">
              <v-btn class="border" @click="getBTC">Отримати BTC</v-btn>
            </v-col>
            <v-col>
              Дата/время
              <div class="border px-2">
                <!-- @input="setTime" -->
                <v-datetime-picker
                  ref="datetime"
                  :time-picker-props="timeProps"
                  :datetime="datetime"
                >
                </v-datetime-picker></div
            ></v-col>
          </v-row>
        </v-card-text>
        <v-divider></v-divider>

        <v-card-actions>
          <v-row>
            <v-col>
              <v-btn
                color="darken-1"
                block
                class="border"
                @click="
                  dial = false;
                  selected = [];
                  closeDialog();
                "
              >
                Відмінити
              </v-btn>
            </v-col>

            <v-col>
              <v-btn
                color="dark primary"
                block
                height="100%"
                :disabled="selectedStatus == 10 && depozit_val < 1 && text_message == ''"
                @click="
                  writeText();
                  putSelectedLidsDB();
                  dial = false;
                  closeDialog();
                "
              >
                Відправити
              </v-btn>
            </v-col>
          </v-row>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
import logtel from "./logtel";
export default {
  components: {
    logtel,
  },
  props: ["user"],
  data: () => ({
    webphone: {},
    timeProps: { format: "24hr" },
    dial: false,
    depozit: 0,
    depozit_val: "",
    componentKey: 0,
    text_message: '',
    tel: "",
    lid_id: "",
    expanded: [],
    singleExpand: true,
    datetime: "",
    userid: null,
    users: [],
    disableuser: 0,
    statuses: [],
    filterstatuses: [],
    selectedStatus: 0,
    filterStatus: 0,
    filterProviders: 0,
    providers: [],
    selected: [],
    todayItems: [],
    lids: [],
    search: "",
    filtertel: "",
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
      { text: "Дата", value: "date" },
      { text: "Перезвон", value: "ontime" },
      { text: "Сообщение", value: "text" },
      { text: "Адрес", value: "address" },
    ],
    headerstime: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Поставщик", value: "provider" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
      { text: "Дата", value: "date" },
      { text: "Сообщение", value: "text" },
      { text: "Адрес", value: "address" },

      { text: "", value: "actions", sortable: false },
    ],
    parse_header: [],
    sortOrders: {},
    sortKey: "tel",
    hm: 0,
    snackbar: false,
    message: "",
  }),
  mounted: function () {
    this.getProviders();
    this.getStatuses();
  },
  created() {},
  watch: {
    datetime: function (newval, oldval) {
      if ((newval == null || newval != oldval) && this.lid_id != "") {
        this.setTime();
      }
    },
  },
  computed: {
    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.lids.filter((i) => {
        return (
          (!this.filterStatus || i.status_id == this.filterStatus) &&
          (!this.filterProviders || i.provider_id == this.filterProviders) &&
          (!this.filtertel || reg.test(i.tel)) &&
          (!this.todayItems ||
            !this.todayItems.filter((e) => e.id == i.id).length)
        );
      });
    },
  },
  methods: {
    wp_call(item) {
      window.open(
        `/webphone/softphone.html?wp_serveraddress=${
          this.$props.user.sip_server
        }&wp_username=${this.$props.user.sip_login}&wp_password=${
          this.$props.user.sip_password
        }&wp_callto=${this.$props.user.sip_prefix + item.tel}`,
        "softphone",
        "width=350,height=540"
      );
    },
    filter: function (evt) {
      evt = evt ? evt : window.event;
      let expect = evt.target.value.toString() + evt.key.toString();

      if (!/^[-+]?[0-9]*\.?[0-9]*$/.test(expect)) {
        evt.preventDefault();
      } else {
        return true;
      }
    },
    qtytel(id) {
      const self = this;
      let data = {};
      data.lid_id = id;
      data.user_id = this.$props.user.id;
      axios
        .post("/api/qtytel", data)
        .then((res) => {
          // self.lids.find((i) => i.id == data.lid_id).qtytel = res.data;
        })
        .catch((error) => console.log(error));
    },
    copyTo(address) {
      this.message = "Copied to clipboard";
      this.snackbar = true;
      if (address == "") {
        this.message = "Нема вільних адресів";
        return;
      }
      this.changeDateBTC(address)
      if (navigator.clipboard && window.isSecureContext) {
        // navigator clipboard api method'
        return navigator.clipboard.writeText(address);
      } else {
        // text area method
        let textArea = document.createElement("textarea");
        textArea.value = address;
        // make the textarea out of viewport
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        return new Promise((res, rej) => {
          // here the magic happens
          document.execCommand("copy") ? res() : rej();
          textArea.remove();
        });
      }
    },
    changeDateBTC(address){
      const self = this;
      let data = {};
      data.address = address;
      axios
        .post("/api/changeDateBTC", data)
        .catch((error) => console.log(error));
    },
    getBTC() {
      const self = this;
      let data = {};
      data.id = this.selected[0].id;
      data.user_id = this.selected[0].user_id;
      data.tel = this.selected[0].tel;
      axios
        .post("/api/getBTC", data)
        .then((res) => {
          if (res.data.address == undefined) {
            self.copyTo("");
            return;
          }
          self.lids.find((i) => i.id == data.id).address = res.data.address;
          self.copyTo(res.data.address);
        })
        .catch((error) => console.log(error));
    },
    hideStatus(id) {
      // show only deposit
      if (this.selected.length && this.selected[0].status_id == 10) {
        if (id != 10) {
          return true;
        }
      }
      // show only deposit & callback
      // if (this.selected.length && this.selected[0].status_id == 9) {
      //   if (id != 9 && id != 10) {
      //     return true;
      //   }
      // }
      return false;
    },
    writeText() {
      if (this.text_message.length > 0) {
        this.lids.find((i) => i.id == this.lid_id).text = this.text_message;
      }
    },
    openDialog(i) {
      let self = this;
      if (self.selected.length > 0 && self.selected[0].id != i.id) {
        this.selected = this.expanded = [];
      }
      this.text_message = "";
      this.lid_id = i.id;
      this.selected = [i];
      // console.log(self.selected);
      this.selectedStatus = i.status_id;
      this.expanded = this.selected;
      self.dial = true;
      setTimeout(() => {
        let self2 = self;
        self2.$refs.datetime.date =
          i.ontime != null ? i.ontime.substring(0, 10) : "";
        self2.$refs.datetime.time =
          i.ontime != null ? i.ontime.substring(11, 16) : "";
      }, 400);
    },
    closeDialog() {
      this.$refs.datetime.date = "";
      this.$refs.datetime.time = "";
      this.selected = [];
      this.selectedStatus = 0;
    },
    getHm() {
      let self = this;
      axios
        .get("/api/getHmLidsUser/" + self.$props.user.id)
        .then((res) => {
          if (res.status == 200) {
            self.hm = res.data;
            if (localStorage.hm) {
              if (localStorage.hm == self.hm) {
                return;
              } else {
                self.message = "У вас изменилось количество лидов!";
                self.snackbar = true;
                localStorage.hm = self.hm;
              }
            } else {
              self.hm = self.lids.length;
              localStorage.hm = self.hm;
            }
          }
        })
        .catch((error) => console.log(error));
    },
    nextdep(status_id) {
      if (status_id != 10) return;
      this.depozit = true;
    },
    forceRerender() {
      this.componentKey += 1;
    },
    deleteTime(item) {
      let self = this;
      let send = {};
      send.ontime = null;
      send.id = item.id;
      axios
        .post("api/Lid/ontime", send)
        .then(function (response) {
          item.ontime = null;
          self.todaylids();
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    setTime() {
      const self = this;
      let send = {};
      if (
        this.$refs.datetime == undefined ||
        this.$refs.datetime.formattedDatetime == ""
      )
        return;
      send.ontime = this.$refs.datetime.formattedDatetime;
      send.id = this.lid_id;

      axios
        .post("api/Lid/ontime", send)
        .then(function (response) {
          //console.log(response);
          self.$refs.datetime.clearHandler();
          self.getLids(self.$props.user.id);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    currentDateTime() {
      const date = new Date();
      // 01, 02, 03, ... 29, 30, 31
      const dd = (date.getDate() < 10 ? "0" : "") + date.getDate();
      // 01, 02, 03, ... 10, 11, 12
      const MM = (date.getMonth() + 1 < 10 ? "0" : "") + (date.getMonth() + 1);
      // 1970, 1971, ... 2015, 2016, ...
      const yyyy = date.getFullYear();
      const time = date.getHours() + ":" + date.getMinutes();
      // create the format you want
      return yyyy + "-" + MM + "-" + dd + " " + time;
    },
    clickrow(i, row) {
      this.$refs.todaytable.expansion = {};
      this.$refs.datatable.expansion = {};

      this.lid_id = i.id;
      row.expand(!row.isExpanded);
    },
    putSelectedLidsDB() {
      const self = this;
      // if (self.selectedStatus == 10 && self.depozit == false) return;
      let send = {};
      let send_el = {};
      let costil = self.filtertel;
      self.filtertel = 1;
      self.filtertel = costil;

      let eli = self.lids.find((obj) => obj.id == self.selected[0].id);

      let st = self.statuses.find((s) => s.id == self.selectedStatus);
      // console.log(st);
      eli.status = st.name;
      eli.status_id = self.selectedStatus;
      eli.updated_at = self.currentDateTime();
      send.id = eli.id;
      send_el.id = eli.id;
      send_el.tel = eli.tel;
      send_el.text = self.text_message;
      send_el.status_id = self.selectedStatus;
      send_el.user_id = eli.user_id;
      send.data = [];
      send.data.push(send_el);
      axios
        .post("api/Lid/updatelids", send)
        .then(function (response) {
          self.forceRerender();
        })
        .catch(function (error) {
          console.log(error);
        });
      this.setTime();
      if (this.depozit_val > 0) {
        self.setDepozit();
      }
    },

    setDepozit() {
      let self = this;
      let send = {};
      send.lid_id = this.selected[0].id;
      send.user_id = this.selected[0].user_id;
      send.depozit = this.depozit_val;
      axios
        .post("api/setDepozit", send)
        .then(function (response) {
          self.depozit_val = "";
          // console.log(response);
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },

    // clickrow(item, row) {
    //   if (!row.isSelected) {
    //     this.tel = item.tel;
    //     this.keytime += 1;
    //     if (item.ontime != null) {
    //       this.datetime = item.ontime.substring(0, 16);
    //     }
    //     this.lid_id = item.id;
    //     this.text = "";
    //     this.depozit_val = "";
    //   } else this.tel = "";
    //   row.select(!row.isSelected);
    // },

    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ name, id, color }) => ({
            name,
            id,
            color,
          }));
          self.filterstatuses = self.statuses.map((e) => e);
          self.filterstatuses.unshift({ name: "Без статуса", id: 0 });
          self.getLids(self.$props.user.id);
        })
        .catch((error) => console.log(error));
    },

    getLids(id) {
      let self = this;
      self.search = "";
      self.filtertel = "";
      self.disableuser = id;
      axios
        .get("/api/userlids/" + id)
        .then((res) => {
          self.lids = Object.entries(res.data).map((e) => e[1]);

          self.lids.map(function (e) {
            // e.user = self.users.find((u) => u.id === e.user_id).fio;
            // delete e.provider_id;
            e.date = e.updated_at.substring(0, 10);
            e.date_created = e.created_at.substring(0, 10);
            // e.mess = e.text;

            if (e.status_id) {
              e.status =
                self.statuses.find((s) => s.id == e.status_id).name || "";
            }
            if (self.providers.find((p) => p.id == e.provider_id)) {
              e.provider = self.providers.find(
                (p) => p.id == e.provider_id
              ).name;
            }
          });

          self.todaylids();
          // setInterval(function () {
          //   self.getHm();
          // }, 180000);
        })
        .then(
          () =>
            function (e) {
              self.lids.map(function (e) {
                e.provider = self.providers.find(
                  (p) => p.id == e.provider_id
                ).name;
              });
            }
        )
        .catch((error) => console.log(error));
    },
    todaylids() {
      const self = this;
      self.todayItems = self.lids.filter(function (l) {
        return (
          new Date(l.ontime).toLocaleDateString() ==
          new Date().toLocaleDateString()
          // new Date(l.ontime).getTime() > 0 &&
          // new Date(l.ontime).getTime() <=
          //   new Date().setUTCHours(23, 59, 59, 999)
        );
      });
      self.todayItems.map(function (t) {
        t.date = new Date(t.ontime).toLocaleTimeString().substring(0, 5);
      });
      self.todayItems.sort(function (a, b) {
        if (a.date > b.date) {
          return 1;
        }
        if (a.date < b.date) {
          return -1;
        }
        // a должно быть равным b
        return 0;
      });
    },
    stylecolor(status_id) {
      // console.log(status_id)
      if (status_id == null) return;
      return this.statuses.find((e) => e.id == status_id).color;
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
          self.providers.unshift({ name: "Все", id: 0 });
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>

<style scoped>
.tel:hover {
  cursor: url(/img/phone-forward.svg) 10 10, none;
  text-decoration: none;
}
.tel {
  display: inline-block;
  margin-right: 1rem;
  color: #000;
}
.tel.active {
  color: #7620df;
  font-weight: bold;
}
#maintable.v-data-table >>> tr {
  outline: 2px solid transparent;
}

#maintable.v-data-table >>> tr:hover,
#maintable.v-data-table >>> tr.v-data-table__selected {
  /* border: 2px solid #000; */
}
td .status_wrp {
  cursor: pointer;
}
#maintable.v-data-table >>> tr.v-data-table__selected {
  border-bottom: transparent !important;
}
#maintable.v-data-table >>> tr.v-data-table__expanded tr:hover {
  border: none;
}
#maintable >>> .text-start {
  padding: 0 !important;
}
.blackborder {
  /* border: 2px solid #000; */
  border-top: transparent !important;
}
.wrp__statuses {
  display: grid;
  grid-template-columns: repeat(4, auto);
}
.wrp__statuses label {
  border: 3px solid transparent;
  font-size: 14px;
}
.wrp__statuses input:checked + label {
  border: 3px solid #7620df;
  border-radius: 30px;
}
.status_wrp span {
  padding: 5px;
  white-space: nowrap;
  overflow: hidden;
  max-width: 85px;
}
.blackborder .row .col {
  margin-top: 1rem;
}
#maintable .v-data-table__wrapper tr td:last-child,
#ontime .v-data-table__wrapper tr td:last-child {
  width: 120px;
}
.fixwidth {
  width: 120px;
  height: 45px;
  overflow: hidden;
  display: block;
}
.hideStatus {
  display: none;
}
</style>
