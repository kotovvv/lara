<template>
  <div>
    <v-container>
      <v-row no-gutters justify="space-around">
        <v-col cols="2">
          <p>Offices</p>
          <v-select
            :items="offices"
            v-model="selected_office_ids"
            item-text="name"
            item-value="id"
            multiple
            class="border px-5"
          ></v-select>
        </v-col>
        <v-col>
          <v-row class="px-4">
            <v-col><p>С Дата</p></v-col>
            <v-col><p>По Дата</p></v-col>
          </v-row>

          <div class="status_wrp wrp_date px-3">
            <v-row align="center">
              <v-col>
                <v-menu
                  v-model="dateFrom"
                  :close-on-content-click="false"
                  :nudge-right="40"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="dateTimeFrom"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    locale="ru-ru"
                    v-model="dateTimeFrom"
                    @input="
                      dateFrom = false;
                      getLidsOnDate();
                    "
                  ></v-date-picker>
                </v-menu>
              </v-col>
              <v-col>
                <v-menu
                  v-model="dateTo"
                  :close-on-content-click="false"
                  :nudge-right="40"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      v-model="dateTimeTo"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    locale="ru-ru"
                    v-model="dateTimeTo"
                    @input="
                      dateTo = false;
                      getLidsOnDate();
                    "
                  ></v-date-picker>
                </v-menu>
              </v-col>
            </v-row>
          </div>
        </v-col>

        <v-col>
          <!-- statuses_lids -->
          <p>Фильтр по статусам</p>
          <v-select
            ref="filterStatus"
            color="red"
            v-model="filterStatus"
            @change="filterStatuses"
            :items="statuses"
            item-text="name"
            item-value="id"
            outlined
            rounded
            :multiple="true"
          >
            <template v-slot:selection="{ item, index }">
              <span v-if="index === 0">{{ item.name }} </span>
              <span v-if="index === 1" class="grey--text text-caption">
                (+{{ filterStatus.length - 1 }} )
              </span>
            </template>
            <template v-slot:item="{ item, attrs }">
              <v-badge
                :value="attrs['aria-selected'] == 'true'"
                color="#7620df"
                dot
                left
              >
                <i
                  :style="{
                    background: item.color,
                    outline: '1px solid grey',
                  }"
                  class="sel_stat mr-4"
                ></i>
              </v-badge>
              {{ item.name }}
            </template>
          </v-select>
        </v-col>

        <v-col>
          <p>Фильтр по поставщикам</p>
          <v-select
            v-model="filterProviders"
            :items="providers"
            item-text="name"
            item-value="id"
            @change="filterStatuses"
            outlined
            rounded
            multiple
          >
            <template v-slot:selection="{ item, index }">
              <span v-if="index === 0">{{ item.name }} </span>
              <span v-if="index === 1" class="grey--text text-caption">
                (+{{ filterProviders.length - 1 }} )
              </span>
            </template>
            <template v-slot:item="{ item, attrs }">
              <v-badge
                :value="attrs['aria-selected'] == 'true'"
                color="#7620df"
                dot
                left
              >
                {{ item.name }}
              </v-badge>
            </template>
          </v-select>
        </v-col>
      </v-row>
      <v-row no-gutters justify="space-around">
        <v-progress-linear
          :active="loading"
          :indeterminate="loading"
          color="deep-purple accent-4"
        ></v-progress-linear>
      </v-row>

    </v-container>
      <v-row>
      <v-col>
        <div class="wrp__statuses">
          <template v-for="(i, x) in Statuses">
            <div class="status_wrp" :key="x">
              <b
                :style="{
                  background: i.color,
                  outline: '1px solid' + i.color,
                }"
                >{{ i.hm }}</b
              >
              <span>{{ i.name }}</span>
            </div>
          </template>
        </div>
      </v-col>
    </v-row>
        <v-row>
      <v-col cols="9">
        <div class="border pa-4">
          <v-data-table
            v-model.lazy.trim="selected"
            id="tablids"
            :headers="headers"
            :search="search"
            :single-select="false"
            item-key="id"
            show-select
            show-expand
            @click:row="clickrow"
            :items="filteredItems"
            :expanded="expanded"
            ref="datatable"
            :footer-props="{
              'items-per-page-options': [],
              'items-per-page-text': '',
            }"
            :disable-items-per-page="true"
            :loading="loading"
            loading-text="Загружаю... Ожидайте"
          >
            <template
              v-slot:top="{ pagination, options, updateOptions }"
              :footer-props="{
                'items-per-page-options': [50, 10, 100, 250, 500, -1],
                'items-per-page-text': '',
              }"
            >
              <v-row>
                <v-col cols="1">
                  <v-row class="mb-5">
                    <span class="mt-5 d-flex pl-2 align-center border"
                      >Отбор
                      <v-text-field
                        class="mx-2 mt-0 pt-0 talign-center nn"
                        @input="selectRow"
                        :max="filteredItems.length"
                        v-model.number="hmrow"
                        hide-details="auto"
                        color="#004D40"
                      ></v-text-field>
                    </span>
                  </v-row>
                </v-col>
                <v-col cols="2" class="mt-1">
                  <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Поиск"
                    single-line
                    hide-details
                    class="border px-2"
                  ></v-text-field>
                </v-col>
                <v-col class="wrp_group">
                  <v-row>
                    <v-checkbox
                      v-model="filterGroups"
                      v-for="(groupa, index) in group"
                      :key="index"
                      :value="groupa.id"
                      class="pt-4"
                    >
                      <template v-slot:label>
                        <div class="img">{{ groupa.fio.slice(0, 3) }}</div>
                        <!-- <img
                          :src="'/storage/' + groupa.pic"
                          :alt="groupa.fio"
                          v-if="groupa.pic"
                        /> -->
                      </template>
                    </v-checkbox>
                  </v-row>
                </v-col>

                <!-- <v-spacer></v-spacer> -->
                <v-col cols="3" class="mt-3">
                  <v-data-footer
                    :pagination="pagination"
                    :options="options"
                    @update:options="updateOptions"
                    :items-per-page-options="[50, 10, 100, 250, 500, -1]"
                    :items-per-page-text="''"
                  />
                </v-col>
              </v-row>
            </template>
            <template v-slot:expanded-item="{ headers, item }">
              <!-- :colspan="headers.length" -->
              <td :colspan="headers.length" class="blackborder">
                <!-- <v-row>
                  <v-col cols="12"> -->

                <logtel :lid_id="item.id" :key="item.id" />
                <!-- </v-col>
                </v-row> -->
              </td>
            </template>
            <template v-slot:footer.prepend>
              <v-col cols="2">
                <v-btn outlined rounded @click="exportXlsx" class="border">
                  <v-icon left> mdi-file-excel </v-icon>
                  Скачать таблицу
                </v-btn>
              </v-col>
              <v-spacer></v-spacer>
              <v-col>
                <h6>Назначение статусов</h6>
              </v-col>
              <v-col cols="3">
                <v-select
                  v-model="selectedStatus"
                  :items="statuses"
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
              <v-col cols="3">
                <v-btn
                  :disable="!selectedStatus && !selected.length"
                  class="border ma-2"
                  outlined
                  rounded
                  @click="changeStatus"
                >
                  Сменить статус
                </v-btn>
              </v-col>
            </template>
          </v-data-table>
        </div>
      </v-col>
      <v-col cols="3">
        <div class="pa-5 w-100 border wrp_users">
          <div class="my-3">Поиск пользователей</div>
          <v-autocomplete
            v-model="selectedUser"
            :items="users"
            label="Выбор"
            item-text="fio"
            item-value="id"
            :return-object="true"
            append-icon="mdi-close"
            outlined
            rounded
            @click:append="clearuser()"
          ></v-autocomplete>

          <div class="scroll-y">
            <v-list>
              <v-radio-group
                id="usersradiogroup"
                ref="radiogroup"
                v-model="userid"
                v-bind="users"
                @change="changeLidsUser"
              >
                <v-expansion-panels ref="akk" v-model="akkvalue">
                  <v-expansion-panel v-for="(item, i) in group" :key="i">
                    <v-expansion-panel-header>
                      <div
                        height="60"
                        width="60"
                        class="img v-expansion-panel-header__icon mr-1"
                      >
                        {{ item.fio.slice(0, 3) }}
                      </div>

                      {{ item.fio }}
                      <div></div>
                    </v-expansion-panel-header>
                    <v-expansion-panel-content>
                      <v-row
                        v-for="user in users.filter(function (i) {
                          return i.group_id == item.group_id;
                        })"
                        :key="user.id"
                      >
                        <v-radio
                          :label="user.fio"
                          :value="user.id"
                          :disabled="disableuser == user.id"
                        >
                        </v-radio>

                        <v-btn
                          class="ml-3"
                          small
                          :color="usercolor(user)"
                          @click="
                            disableuser = user.id;
                            getLidsOnUserOrDate();
                          "
                          :value="user.hmlids"
                          :disabled="disableuser == user.id"
                          >{{ user.hmlids }}</v-btn
                        >
                        <v-chip v-if="user.statnew" label small>
                          {{ user.statnew }}
                        </v-chip>
                      </v-row>
                    </v-expansion-panel-content>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-radio-group>
            </v-list>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import axios from "axios";
import _ from "lodash";
export default {
  mounted: function () {
    this.getOffices();
    this.getProviders();
    this.getStatuses();
  },
  data: () => ({
    loading: false,
    dateFrom: false,
    dateTo: false,
    dateTimeFrom: new Date(new Date().setDate(new Date().getDate() - 7))
      .toISOString()
      .substring(0, 10),
    dateTimeTo: new Date().toISOString().substring(0, 10),
    Statuses: [],
    statuses: [],
    filterStatus: [],
    providers: [],
    filterProviders: [],
    offices: [],
    selected_office_ids: [],
  }),
  computed: {
    filteredItems() {
      return this.lids.filter((i) => {
        return (
          (!this.filterStatus.length ||
            this.filterStatus.includes(i.status_id)) &&
          (!this.filterProviders.length ||
            this.filterProviders.includes(i.provider_id)) &&
          (!this.filterGroups.length || this.filterGroups.includes(i.group_id))
        );
      });
    },
  },
  methods: {
    getOffices() {
      let self = this;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
          self.selected_office_ids.push(self.offices[0].id);
        })
        .catch((error) => console.log(error));
    },
        getLocalDateTime(DateTime) {
      return new Date(
        new Date(DateTime).getTime() -
          new Date(DateTime).getTimezoneOffset() * 60 * 1000
      )
        .toJSON()
        .slice(0, 16)
        .replace("T", " ");
    },
        getLidsOnDate() {
      let self = this;
      this.loading = true;
      let data = {};
      if (this.datetimeFrom == "")
        this.datetimeFrom = new Date(
          new Date().setDate(new Date().getDate() - 14)
        )
          .toISOString()
          .substring(0, 10);
      if (this.datetimeTo == "")
        this.datetimeTo = new Date().toISOString().substring(0, 10);
      data.datefrom = this.getLocalDateTime(this.dateTimeFrom);
      data.dateto = this.getLocalDateTime(this.dateTimeTo);
      data.office_ids = this.selected_office_ids;
      console.log(data)
      axios
        .post("/api/getLidsOnDate", data)
        .then((res) => {
          self.loading = false;
          self.lids = Object.entries(res.data).map((e) => e[1]);
          self.lids.map(function (e) {
            e.date_created = e.created_at.substring(0, 10);
            e.date_updated = e.updated_at.substring(0, 10);
            if (e.status_id) {
              e.status = self.statuses.find((s) => s.id == e.status_id).name;
            }
            if (self.users.find((u) => u.id == e.user_id)) {
              let luser = self.users.find((u) => u.id == e.user_id);
              e.user = luser.fio;
              e.group_id = luser.group_id;
            }

            if (self.providers.find((p) => p.id == e.provider_id)) {
              e.provider = self.providers.find(
                (p) => p.id == e.provider_id
              ).name;
            }
          });
          self.orderStatus();
          self.searchAll = "";
        })
        .then(() => {
          if (localStorage.filterStatus) {
            self.filterStatus = localStorage.filterStatus
              .split(",")
              .map((el) => parseInt(el));
          }
          self.filterStatuses();
        })
        .then(() => {
          if (localStorage.filterProviders) {
            self.filterProviders = localStorage.filterProviders
              .split(",")
              .map((el) => parseInt(el));
          }
        })
        .then(() => {
          if (self.hmrow > 0) {
            const temp = self.hmrow;
            self.hmrow = "";
            self.hmrow = temp;
            self.selectRow();
          }
        })
        .catch((error) => console.log(error));
    },
    getStatuses() {
      let self = this;
      axios
        .get("/api/statuses")
        .then((res) => {
          self.statuses = res.data.map(({ uname, name, id, color, order }) => ({
            uname,
            name,
            id,
            color,
            order,
          }));
          self.filterStatus.push(10)
        })
        .catch((error) => console.log(error));
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(({ name, id }) => ({ name, id }));
          // self.providers.unshift({ name: "выбор", id: 0 });
          // self.getLidsOnDate();
        })
        .catch((error) => console.log(error));
    },
    filterStatuses() {
      const self = this;
      self.Statuses = [];
      let stord = this.filteredItems;
      stord = Object.entries(_.groupBy(stord, "status"));
      stord.map(function (i) {
        //i[0]//name
        //i[1]//array
        let el = self.statuses.find((s) => s.name == i[0]);
        self.Statuses.push({
          id: el.id,
          name: i[0],
          hm: i[1].length,
          order: el.order,
          color: el.color,
        });
      });
      self.Statuses = _.orderBy(self.Statuses, "order");
    },
  },
};
</script>
