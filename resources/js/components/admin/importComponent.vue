<template>
  <div>
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <v-row class="align-center pt-10">
            <v-col cols="1">
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
                    v-model="datetimeFrom"
                    label="С даты"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="datetimeFrom"
                  @input="
                    dateFrom = false;
                    takedates ? getImports() : '';
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
            <v-col cols="1">
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
                    v-model="datetimeTo"
                    label="По дату"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                  ></v-text-field>
                </template>
                <v-date-picker
                  locale="ru-ru"
                  v-model="datetimeTo"
                  @input="
                    dateTo = false;
                    takedates ? getImports() : '';
                  "
                ></v-date-picker>
              </v-menu>
            </v-col>
            <v-checkbox v-model="takedates" @change="getImports"></v-checkbox>
            <v-icon @click="importCallc">mdi-refresh</v-icon>
            <v-col cols="2">
              <v-autocomplete
                v-model="filter_import_provider"
                :items="i_providers"
                label="Фильтр провайдер"
                item-text="name"
                item-value="id"
                outlined
                rounded
                multiple
                clearable="clearable"
                @change="filterAPI"
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
              </v-autocomplete>
            </v-col>
            <v-col cols="2">
              <v-select
                ref="resetStatus"
                label="Переназначить статусы"
                v-model="resetStatus"
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
                    (+{{ resetStatus.length - 1 }} )
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
            <v-btn
              class="btn"
              v-if="importSelected.length || redistributeOffice"
              @click.stop="drawer = !drawer"
              >Назначить на менеджеров</v-btn
            >
            <v-spacer></v-spacer>
          </v-row>
        </v-col>
      </v-row>
      <v-progress-linear
        :active="loading"
        indeterminate
        color="purple"
      ></v-progress-linear>
      <v-row style="height: 80vh; overflow-y: auto">
        <v-col cols="6">
          <v-data-table
            :headers="import_headers"
            item-key="id"
            :items="filter_imports"
            ref="importtable"
            show-select
            :search="search"
            v-model="importSelected"
            @click:row="clickrow"
            :footer-props="{
              'items-per-page-options': [],
              'items-per-page-text': '',
            }"
          >
            <template v-slot:top="{ pagination, options, updateOptions }">
              <v-row>
                <v-col
                  ><v-autocomplete
                    v-model="search"
                    :items="imports"
                    item-text="message"
                    item-value="message"
                    append-icon="mdi-magnify"
                    label="Поиск"
                    hide-details
                    outlined
                  ></v-autocomplete
                ></v-col>
                <v-col>
                  <!-- <v-data-footer
                      :pagination="pagination"
                      :options="options"
                      @update:options="updateOptions"
                      items-per-page-text=""
                  /> -->
                </v-col>
              </v-row>
            </template>

            <template v-slot:item.start="{ item }">
              <div>{{ item.start.substring(0, 10) }}</div>
              <div>{{ item.start.substring(11) }}</div>
            </template>
            <template v-slot:item.provider="{ item }">
              <div>{{ item.provider }}</div>
              <div>{{ item.baer }}</div>
            </template>
            <template v-slot:item.message="{ item }">
              {{ item.message }}
              <v-icon small class="mr-2" @click.stop="editItem(item)">
                mdi-pencil
              </v-icon>
            </template>
            <template
              v-slot:item.id="{ item }"
              v-if="$attrs.user.office_id == 0"
            >
              <v-btn @click.stop="deleteImport(item)" plain
                ><v-icon>mdi-delete</v-icon></v-btn
              >
            </template>
          </v-data-table>
        </v-col>
        <v-col cols="6" style="margin-top: 3.5rem">
          <table class="table">
            <tbody>
              <tr>
                <td class="text-center" width="110">&nbsp;&nbsp;&nbsp;</td>
                <td width="140">Дата</td>
                <td width="150">Поставщик</td>
                <td class="text-center" width="150">Сумма</td>
                <td class="text-center" width="53">L/A</td>
                <td class="text new text-center" width="150">NEW</td>
                <td class="text callback text-center" width="150">Callback</td>
                <td class="text deposit text-center" width="150">Deposit</td>
                <td class="text pending text-center" width="150">Pending</td>
                <td class="text potential text-center" width="150">
                  Potential
                </td>
                <td class="text-center" width="150">Кол-во</td>
                <td class="text-center">GEO</td>
              </tr>
            </tbody>
          </table>
          <div style="max-height: 70vh; overflow-y: auto">
            <v-expansion-panels accordion>
              <v-expansion-panel
                v-for="apigr in Object.keys(apigroup)"
                :key="apigr"
              >
                <v-expansion-panel-header>
                  <table>
                    <tbody>
                      <tr>
                        <td class="text-center" width="100">
                          &nbsp;&nbsp;&nbsp;
                        </td>
                        <td width="140">
                          {{ apigr.substring(0, 10) }}
                        </td>
                        <td class="overflow-hidden" width="150">
                          {{ apigr.substring(10) }}
                        </td>
                        <td width="125">&nbsp;&nbsp;&nbsp;</td>
                        <td width="53">&nbsp;</td>

                        <td class="text new text-center" width="150">
                          <span class="fz17">{{
                            sumField("hmnew", apigroup[apigr])
                          }}</span>
                        </td>
                        <td class="text callback text-center" width="150">
                          <span class="fz17">{{
                            sumField("hmcb", apigroup[apigr])
                          }}</span>
                        </td>
                        <td class="text deposit text-center" width="150">
                          <span class="fz17">{{
                            sumField("hmdp", apigroup[apigr])
                          }}</span>
                        </td>
                        <td class="text pending text-center" width="150">
                          <span class="fz17">{{
                            sumField("hmpnd", apigroup[apigr])
                          }}</span>
                        </td>
                        <td class="text potential text-center" width="150">
                          <span class="fz17">{{
                            sumField("hmpot", apigroup[apigr])
                          }}</span>
                        </td>
                        <td class="text text-center" width="110">
                          <span class="fz17">{{
                            sumField("hm", apigroup[apigr])
                          }}</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <v-data-table
                    :headers="import_provider_headers"
                    hide-default-header
                    item-key="id"
                    :items="apigroup[apigr]"
                    show-select
                    v-model="importSelected"
                    @click:row="clickrow"
                    :footer-props="{
                      'items-per-page-options': [],
                      'items-per-page-text': '',
                    }"
                  >
                    <template v-slot:item.start="{ item }">
                      <td width="150">
                        {{ item.start }}
                      </td>
                    </template>
                    <template v-slot:item.provider="{ item }">
                      <td width="150">
                        {{ item.provider }}
                      </td>
                    </template>
                    <template v-slot:item.sum="{ item }">
                      <td class="text-center" width="150">
                        {{ item.sum
                        }}<v-icon
                          small
                          class="mr-2"
                          @click.stop="editItem(item)"
                        >
                          mdi-pencil
                        </v-icon>
                      </td>
                    </template>
                    <template v-slot:item.cp="{ item }">
                      <td class="text-center" width="53">
                        {{ item.cp }}
                      </td>
                    </template>
                    <template v-slot:item.hmnew="{ item }">
                      <td class="text-center new fz17" width="150">
                        {{ item.hmnew }}
                      </td>
                    </template>
                    <template v-slot:item.callback="{ item }">
                      <td class="text-center callback fz17" width="150">
                        {{ item.callback }}
                      </td>
                    </template>
                    <template v-slot:item.deposit="{ item }">
                      <td class="text-center deposit fz17" width="150">
                        {{ item.deposit }}
                      </td>
                    </template>
                    <template v-slot:item.potential="{ item }">
                      <td class="text-center potential fz17" width="150">
                        {{ item.potential }}
                      </td>
                    </template>
                    <template v-slot:item.hm="{ item }">
                      <td class="text-center" width="80">
                        {{ item.hm }}
                      </td>
                    </template>
                  </v-data-table>
                </v-expansion-panel-content>
              </v-expansion-panel>
            </v-expansion-panels>
          </div>
        </v-col>
        <v-col cols="3"></v-col>
        <v-col cols="12" id="info_prov">
          {{ item.name }} {{ item.start }}
          <v-btn class="btn mx-1" @click="getHistory">История</v-btn>
          <span v-if="item.name">Пользователи: </span>
          <span v-for="usr in holdLidsUsers" :key="usr[0]">
            {{ usr[0] }}.
          </span>
        </v-col>
        <v-col cols="12" v-for="office in lidsByOffice" :key="office.name">
          <div class="d-flex align-center">
            <v-checkbox
              style="font-size: 1.2rem; font-weight: bold"
              v-model="redistributeOffice"
              hide-details
              :label="office.name + ' - Total ' + office.lids.length"
              :value="office.name"
            ></v-checkbox>
          </div>
          <v-col cols="12" v-if="office.statuses">
            <div id="wrp_stat" class="wrp__statuses">
              <!-- <div

                v-for="u in usersStatuses"
                :key="u.user_id"
              >
                {{ u.user }} -->
              <template v-for="i in office.statuses">
                <div
                  class="status_wrp"
                  :class="{
                    active:
                      filterOfficeTabl.includes(office.name) &&
                      filterStatusTabl.includes(i.id),
                  }"
                  :key="i.id"
                  @click="filterOfficeStatus(office.name, i.id)"
                >
                  <b
                    :style="{
                      background: i.color,
                      outline: '1px solid' + i.color,
                    }"
                    >{{ i.hm }}</b
                  >
                  <span>{{ i.name }}</span>
                  <v-btn
                    v-if="
                      filterOfficeTabl.includes(office.name) &&
                      filterStatusTabl.includes(i.id)
                    "
                    icon
                    x-small
                  >
                    <v-icon>mdi-close</v-icon>
                  </v-btn>
                </div>
              </template>
              <!-- </div> -->
            </div>
          </v-col>
        </v-col>
        <v-col cols="12" v-if="Statuses">
          <div id="wrp_stat" class="wrp__statuses">
            <template v-for="i in Statuses">
              <div
                class="status_wrp"
                :class="{
                  active:
                    filterOfficeTabl.length == 0 &&
                    filterStatusTabl.includes(i.id),
                }"
                :key="i.id"
                @click="filterOfficeStatus(0, i.id)"
              >
                <b
                  :style="{
                    background: i.color,
                    outline: '1px solid' + i.color,
                  }"
                  >{{ i.hm }}</b
                >
                <span>{{ i.name }}</span>
                <v-btn
                  v-if="
                    filterOfficeTabl.length == 0 &&
                    filterStatusTabl.includes(i.id)
                  "
                  icon
                  x-small
                >
                  <v-icon>mdi-close</v-icon>
                </v-btn>
              </div>
            </template>
          </div>
        </v-col>
        <v-col cols="12" v-if="historyStatus.length">
          <p>Переназначенные</p>
          <div id="wrp_stat" class="wrp__statuses">
            <template v-for="i in historyStatus">
              <div :key="i.id">
                <div class="status_wrp">
                  <b
                    :style="{
                      background: i.color,
                      outline: '1px solid' + i.color,
                    }"
                    >{{ i.hm }}</b
                  >
                  <span>{{ i.name }}</span>
                </div>
              </div>
            </template>
            <!-- </div> -->
          </div>
        </v-col>
        <v-col cols="12">
          <div class="mt-3">
            <div
              class="wrp__statuses"
              v-for="(u, ix) in history"
              :key="u.created_at"
            >
              <h5>Start{{ history.length - ix }} {{ u.created_at }}</h5>
              <template v-for="i in JSON.parse(u.statuses)">
                <div class="status_wrp" :key="i.id">
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
          </div>
        </v-col>

        <v-col cols="12" v-if="leads.length">
          <div class="border pa-4">
            <v-data-table
              id="tabimplids"
              :headers="headers_leads"
              item-key="id"
              :items="filteredLeads"
              :footer-props="{
                'items-per-page-options': [],
                'items-per-page-text': '',
              }"
              :disable-items-per-page="true"
              :loading="loading"
              loading-text="Загружаю... Ожидайте"
              ref="datatablelids"
              @click:row="getLog"
              :single-expand="true"
            >
              <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length" class="blackborder">
                  <v-row>
                    <v-col cols="12">
                      <logtel :lid_id="item.id" :key="item.id" />
                    </v-col>
                  </v-row>
                </td>
              </template>
              <template
                v-slot:top="{ pagination, options, updateOptions }"
                :footer-props="{
                  'items-per-page-options': [50, 10, 100, 250, 500, -1],
                  'items-per-page-text': '',
                }"
              >
                <v-row>
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

                  <v-col cols="3">
                    <v-select
                      label="Cтатус в файл"
                      ref="filterStatus"
                      color="red"
                      v-model="filterStatus"
                      :items="Statuses"
                      item-text="name"
                      item-value="id"
                      outlined
                      rounded
                      :multiple="true"
                    >
                      <template v-slot:selection="{ item, index }">
                        <span v-if="index === 0">{{ item.name }} </span>
                        <span
                          v-if="index === 1"
                          class="grey--text text-caption"
                        >
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
                    <div class="d-flex">
                      <v-select
                        label="Фильтр статус"
                        v-model="filterStatusTabl"
                        :items="Statuses"
                        item-text="name"
                        item-value="id"
                        outlined
                        rounded
                        :multiple="true"
                      >
                        <template v-slot:selection="{ item, index }">
                          <span v-if="index === 0">{{ item.name }} </span>
                          <span
                            v-if="index === 1"
                            class="grey--text text-caption"
                          >
                            (+{{ filterStatusTabl.length - 1 }} )
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
                      <v-select
                        label="Фильтр office"
                        v-model="filterOfficeTabl"
                        :items="lidsByOffice"
                        item-text="name"
                        item-value="name"
                        outlined
                        rounded
                        :multiple="true"
                      ></v-select>
                    </div>
                  </v-col>
                  <v-col cols="3">
                    <v-select
                      v-model="exportfields"
                      :items="exportfield"
                      label="Поля для експорта"
                      multiple
                      outlined
                    ></v-select
                  ></v-col>
                  <v-col cols="3">
                    <v-btn
                      outlined
                      rounded
                      @click="exportImportedXlsx"
                      class="border"
                    >
                      <v-icon left> mdi-file-excel </v-icon>
                      Скачать таблицу
                    </v-btn>
                  </v-col>
                </v-row>
              </template>
            </v-data-table>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
import axios from "axios";
export default {
  name: "importComponent",
  data() {
    return {
      loading: false,
      takedates: true,
      dateFrom: false,
      dateTo: false,
      dateProps: { locale: "ru-RU", format: "24hr" },
      datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 3))
        .toISOString()
        .substring(0, 10),
      datetimeTo: new Date().toISOString().substring(0, 10),
    };
  },
  methods: {
    async deleteImport(item) {
      const self = this;
      if (
        await this.$refs.confirm.open(
          "Видалити???",
          "Усі імпортовані ліди за вказаним записом будуть видалені безповоротно. Видаляємо?"
        )
      ) {
        axios
          .post("api/deleteImportedLids", item)
          .then(function (response) {
            self.getImports();
          })
          .catch(function (error) {
            console.log(error);
          });
      }
    },
    getImports() {
      let self = this;
      self.loading = true;
      let datefrom = self.datetimeFrom,
        dateto = self.datetimeTo;
      if (self.takedates == 0) {
        datefrom = 0;
        dateto = 0;
      }
      axios
        .get("/api/imports/" + datefrom + "/" + dateto)
        .then((res) => {
          self.imports = res.data.map(
            ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
              load_key,
              sum,
              cp,
              baer,
              hmnew,
              hm,
              hmcb,
              hmdp,
              hmpnd,
              hmpot,
              hm_json,
            }) => ({
              id,
              start,
              end,
              provider,
              provider_id,
              user,
              message,
              group_id,
              load_key,
              sum,
              cp,
              baer,
              hmnew,
              hm,
              hmcb,
              hmdp,
              hmpnd,
              hmpot,
              hm_json,
            })
          );
          if (self.$attrs.user.office_id > 0) {
            let office_id = self.$attrs.user.office_id;
            self.imports = self.imports.map((i) => {
              let hm_json = JSON.parse(i.hm_json);
              i.hmnew = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmnew;
              i.hm = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hm;
              i.hmcb = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmcb;
              i.hmdp = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmdp;
              i.hmpnd = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmpnd;
              i.hmpot = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmpot;
              return i;
            });
          }

          let a_prov = _.uniq(
            _.map(self.imports, (el) => {
              return el.provider_id;
            })
          );
          self.i_providers = self.providers.filter((i) => {
            return a_prov.includes(i.id);
          });

          self.ImportedProvLids();
          self.loading = false;
        })
        .catch((error) => console.log(error));
    },
  },
};
</script>
