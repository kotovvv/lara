 <template>
  <div class="csv">
    <v-snackbar v-model="snackbar" top right timeout="-1">
      <v-card-text v-html="message"></v-card-text>
      <template v-slot:action="{ attrs }">
        <v-btn color="pink" text v-bind="attrs" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>
    <v-tabs v-model="tab" background-color="primary" dark>
      <v-tab> XLSX </v-tab>
      <v-tab> Imports </v-tab>

      <v-tab v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0">
        ВТС
      </v-tab>
      <v-tab>CHECK DUBLIKATE MAIL</v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
      <!-- XLSX -->
      <v-tab-item>
        <importxlsx :user="$attrs.user"></importxlsx>
      </v-tab-item>
      <!-- CSV -->
      <v-tab-item>
        <v-progress-linear
          :active="loading"
          indeterminate
          color="purple"
        ></v-progress-linear>
        <v-main v-if="parse_csv.length && files">
          <v-row class="csv">
            <v-col cols="8">
              <v-card>
                <v-container fluid>
                  <v-row>
                    <v-col cols="4">
                      <v-card-title>
                        <v-text-field
                          v-model="search"
                          append-icon="mdi-magnify"
                          label="Поиск"
                          single-line
                          hide-details
                        ></v-text-field>
                      </v-card-title>
                    </v-col>
                    <v-col cols="3">
                      <v-card-title>
                        <v-text-field
                          v-model.lazy.trim="filtertel"
                          append-icon="mdi-phone"
                          label="Начальные цифры"
                          single-line
                          hide-details
                        ></v-text-field>
                      </v-card-title>
                    </v-col>
                  </v-row>
                </v-container>
                <v-data-table
                  v-model.lazy.trim="selected"
                  :headers="headers"
                  :search="search"
                  :single-select="false"
                  item-key="tel+afilyator"
                  show-select
                  :items="filteredItems"
                  ref="datatable"
                  :loading="loading"
                >
                </v-data-table>
              </v-card>
            </v-col>
            <v-col cols="4">
              <v-card height="100%" class="pa-5">
                Укажите пользователя для лидов
                <v-list>
                  <v-radio-group
                    @change="putSelectedLidsDB"
                    ref="radiogroup"
                    v-model="userid"
                    v-bind="users"
                    id="usersradiogroup"
                  >
                    <v-radio
                      :value="user.id"
                      v-for="user in users"
                      :key="user.id"
                    >
                      <template v-slot:label>
                        {{ user.fio }}
                        <v-badge
                          :content="user.hmlids"
                          :value="user.hmlids"
                          :color="usercolor(user)"
                          overlap
                        >
                          <v-icon large v-if="user.role_id === 2">
                            mdi-account-group-outline
                          </v-icon>
                          <v-icon large v-else> mdi-account-outline </v-icon>
                        </v-badge>
                      </template>
                    </v-radio>
                  </v-radio-group>
                </v-list>
              </v-card>
            </v-col>
          </v-row>
        </v-main>
        <div v-else>
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
                <v-checkbox
                  v-model="takedates"
                  @change="getImports"
                ></v-checkbox>
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
                <v-col cols="1">
                  <v-autocomplete
                    v-model="filter_geo"
                    :items="i_geos"
                    label="GEO"
                    outlined
                    rounded
                    :clearable="clearable"
                    @change="filter_geo = filter_geo || []"
                  >
                  </v-autocomplete>
                </v-col>
                <v-col cols="2">
                  <v-col
                    ><v-autocomplete
                      v-model="search"
                      :items="imports"
                      item-text="message"
                      item-value="message"
                      append-icon="mdi-magnify"
                      label="Поиск"
                      outlined
                      rounded
                    ></v-autocomplete
                  ></v-col>
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
          <!-- style="height: 80vh; overflow-y: auto" -->
          <v-container fluid id="tabs">
            <v-row>
              <v-col cols="9">
                <v-tabs v-model="tabimport">
                  <v-tab value="files">CPL</v-tab>
                  <v-tab value="api">CPA</v-tab>
                </v-tabs>
                <v-tabs-items v-model="tabimport">
                  <v-tab-item :key="'files'">
                    <v-data-table
                      height="80vh"
                      :headers="import_headers"
                      :fixed-header="true"
                      item-key="id"
                      id="cpl"
                      :items="filter_imports"
                      ref="importtable"
                      show-select
                      :search="search"
                      v-model="importSelected"
                      @click:row="clickrow"
                      hide-default-footer
                      disable-pagination
                    >
                      <template slot="body.prepend">
                        <tr>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td class="text-center new">
                            {{ sumField("hmnew") }}
                          </td>
                          <td class="text-center renew">
                            {{ sumField("hmrenew") }}
                          </td>
                          <td class="text-center callback">
                            {{ sumField("hmcb") }}
                          </td>
                          <td class="text-center deposit">
                            {{ sumField("hmdp") }}
                          </td>
                          <td class="text-center pending">
                            {{ sumField("hmpnd") }}
                          </td>
                          <td class="text-center potential">
                            {{ sumField("hmpot") }}
                          </td>
                          <td class="text-center noans">
                            {{ sumField("noans") }}
                          </td>
                          <td class="text-center nointerest">
                            {{ sumField("nointerest") }}
                          </td>
                          <td></td>
                        </tr>
                      </template>
                      <template v-slot:item.hmnew="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(8)"
                        >
                          {{ item.hmnew }}
                        </div>
                      </template>
                      <template v-slot:item.hmrenew="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(33)"
                        >
                          {{ item.hmrenew }}
                        </div>
                      </template>
                      <template v-slot:item.hmcb="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(9)"
                        >
                          {{ item.hmcb }}
                        </div>
                      </template>
                      <template v-slot:item.hmdp="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(10)"
                        >
                          {{ item.hmdp }}
                        </div>
                      </template>
                      <template v-slot:item.hmpnd="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(20)"
                        >
                          {{ item.hmpnd }}
                        </div>
                      </template>
                      <template v-slot:item.hmpot="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(32)"
                        >
                          {{ item.hmpot }}
                        </div>
                      </template>
                      <template v-slot:item.hmnoans="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(7)"
                        >
                          {{ item.hmnoans }}
                        </div>
                      </template>
                      <template v-slot:item.hmnointerest="{ item }">
                        <div
                          class="pointer"
                          @click="changeFilterStatusClick(12)"
                        >
                          {{ item.hmnointerest }}
                        </div>
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
                        <v-icon @click.stop="deleteImport(item)" size="medium"
                          >mdi-delete</v-icon
                        >
                      </template>
                    </v-data-table>
                  </v-tab-item>

                  <v-tab-item :key="'api'">
                    <v-data-table
                      :headers="providerHeaders"
                      :items="filter_importsProvLeads"
                      item-value="id"
                      id="provTable"
                      show-expand
                      single-expand
                      hide-default-footer
                      class="elevation-1 common-table"
                      :expanded.sync="expanded"
                      @click:row="clickrowd"
                      fixed-header
                      height="80vh"
                      disable-pagination
                    >
                      <template slot="body.prepend">
                        <tr>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td class="text-center new">
                            {{ sumField("hmnew") }}
                          </td>
                          <td class="text-center renew">
                            {{ sumField("hmrenew") }}
                          </td>
                          <td class="text-center callback">
                            {{ sumField("hmcb") }}
                          </td>
                          <td class="text-center deposit">
                            {{ sumField("hmdp") }}
                          </td>
                          <td class="text-center pending">
                            {{ sumField("hmpnd") }}
                          </td>
                          <td class="text-center potential">
                            {{ sumField("hmpot") }}
                          </td>
                          <td class="text-center noans">
                            {{ sumField("noans") }}
                          </td>
                          <td class="text-center nointerest">
                            {{ sumField("nointerest") }}
                          </td>
                        </tr>
                      </template>
                      <template v-slot:expanded-item="{ item }">
                        <td :colspan="providerHeaders.length + 1">
                          <v-data-table
                            :headers="dateHeaders"
                            :items="item.dates"
                            item-value="id"
                            id="dateTable"
                            show-expand
                            single-expand
                            :expanded.sync="expandedate"
                            @click:row="toggleExpandDate"
                            hide-default-header
                            hide-default-footer
                            class="common-table date-table"
                          >
                            <template v-slot:expanded-item="{ item: dateItem }">
                              <td :colspan="providerHeaders.length + 1">
                                <v-data-table
                                  :headers="geoHeaders"
                                  :items="dateItem.geo"
                                  item-value="id"
                                  item-key="id"
                                  id="geoTable"
                                  hide-default-header
                                  hide-default-footer
                                  class="common-table"
                                >
                                  <template v-slot:item="{ item: geoItem }">
                                    <tr>
                                      <td>
                                        <v-checkbox
                                          :input-value="isSelected(geoItem)"
                                          @change="clickGeo(geoItem)"
                                        ></v-checkbox>
                                      </td>
                                      <td
                                        class="text-start common-column pointer pl-8"
                                        width="100px"
                                        @click="clickrow(geoItem)"
                                      >
                                        {{ geoItem.geo }}
                                      </td>
                                      <td
                                        class="text-center common-column pointer"
                                        width="100px"
                                        @click="clickrow(geoItem)"
                                      >
                                        {{ geoItem.cp }}
                                      </td>
                                      <td
                                        class="text-center common-column pointer"
                                        width="100px"
                                      >
                                        {{ geoItem.sum
                                        }}<v-icon
                                          small
                                          style="position: absolute"
                                          @click.stop="editItem(geoItem)"
                                        >
                                          mdi-pencil
                                        </v-icon>
                                      </td>
                                      <td
                                        class="text-center common-column pointer fz17"
                                        width="100px"
                                        @click="clickrow(geoItem)"
                                      >
                                        {{ geoItem.hm }}
                                      </td>
                                      <td
                                        class="text-center common-column new pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(8);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmnew }}
                                      </td>
                                      <td
                                        class="text-center common-column renew pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(33);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmrenew }}
                                      </td>
                                      <td
                                        class="text-center common-column callback pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(9);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmcb }}
                                      </td>
                                      <td
                                        class="text-center common-column deposit pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(10);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmdp }}
                                      </td>
                                      <td
                                        class="text-center common-column pending pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(20);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmpnd }}
                                      </td>
                                      <td
                                        class="text-center common-column potential pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(32);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmpot }}
                                      </td>
                                      <td
                                        class="text-center common-column noans pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(7);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmnoans }}
                                      </td>
                                      <td
                                        class="text-center common-column nointerest pointer"
                                        width="100px"
                                        @click="
                                          changeFilterStatusClick(12);
                                          clickrow(geoItem);
                                        "
                                      >
                                        {{ geoItem.hmnointerest }}
                                      </td>
                                    </tr>
                                  </template>
                                </v-data-table>
                              </td>
                            </template>
                          </v-data-table>
                        </td>
                      </template>
                    </v-data-table>
                  </v-tab-item>
                </v-tabs-items>
              </v-col></v-row
            ></v-container
          >
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
                v-model.lazy.trim="selectedTop"
                show-select
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
                    <v-col cols="2">
                      <v-btn v-if="selectedTop.length" @click="setTop"
                        >Назначить приоритет</v-btn
                      ></v-col
                    >
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
        </div>
      </v-tab-item>
      <!-- ВТС -->
      <v-tab-item v-if="$attrs.user.role_id == 1 && $attrs.user.office_id == 0">
        <importBTC></importBTC>
      </v-tab-item>
      <!-- CHECK DUBLIKATE MAIL -->
      <v-tab-item>
        <v-container fluid>
          <v-row>
            <v-col class="d-flex align-center radiolabel">
              <v-radio-group v-model="email_tel" row>
                <v-radio label="email" value="email"></v-radio>
                <v-radio label="телефон" value="tel"></v-radio>
              </v-radio-group>
              <span class="label">Выгрузка дублей за </span>
              <v-text-field
                style="max-width: 3rem; padding-top: 0"
                class="inline-block center"
                @keypress="filter()"
                v-model.number="hmmonth"
                hide-details="auto"
              ></v-text-field>
              <span class="label"> месяц(ев)</span>
            </v-col>
          </v-row>
          <v-row>
            <v-col cols="6">
              <v-textarea
                class="border pa-3"
                v-model="list_email"
                label="Почтовые адреса или телефоны "
              ></v-textarea>
            </v-col>
            <v-col cols="6">
              <v-file-input
                v-model="file_emails"
                label="загрузить txt"
                show-size
                truncate-length="24"
                @change="onFileChange"
              ></v-file-input>
              <v-btn @click="checkEmails" v-if="list_email" class="primary"
                >Проверить<v-progress-circular
                  v-if="loading"
                  indeterminate
                  color="amber"
                ></v-progress-circular
              ></v-btn>
            </v-col>
            <v-col cols="12">
              <div class="wrp__statuses">
                <template v-for="(i, x) in d_statuses">
                  <div
                    class="status_wrp"
                    :class="{
                      borderactive: filter_status.includes(i.id),
                      bordernot: !filter_status.includes(i.id),
                    }"
                    :key="x"
                    @click="changeFilterStatus(i.id)"
                  >
                    <b
                      :style="{
                        background: i.color,
                        outline: '1px solid' + i.color,
                      }"
                      >{{ i.hm }}</b
                    >
                    <span>{{ i.name }}</span>
                    <v-btn v-if="filter_status.includes(i.id)" icon x-small>
                      <v-icon>mdi-close</v-icon>
                    </v-btn>
                  </div>
                </template>
              </div>
            </v-col>
            <v-col cols="12" v-if="duplicate_leads.length || out_db.length">
              <v-data-table
                :headers="duplicate_leads_headers"
                item-key="id"
                :items="filtereduplicate_leads"
                ref="duplicatetable"
                @click:row="clickrowd"
                show-expand
                :expanded.sync="expanded"
                :footer-props="{
                  'items-per-page-options': [100, 200, 500, -1],
                  'items-per-page-text': '',
                }"
                v-model.lazy.trim="selectedTop"
                show-select
              >
                <template v-slot:expanded-item="{ headers, item }">
                  <td :colspan="headers.length" class="blackborder">
                    <logtel :lid_id="item.id" :key="item.id" />
                  </td>
                </template>
                <template v-slot:top="{ pagination, options, updateOptions }">
                  <v-row>
                    <v-col cols="2">
                      <v-btn v-if="selectedTop.length" @click="setTop"
                        >Назначить приоритет</v-btn
                      ></v-col
                    >
                    <v-col>
                      <v-autocomplete
                        v-model="filter_provider"
                        label="Фильтр поставщик"
                        :items="d_providers"
                        item-text="name"
                        item-value="id"
                        outlined
                        rounded
                        multiple
                        :menu-props="{ maxHeight: '80vh' }"
                        clearable="clearable"
                      >
                        <template v-slot:selection="{ item, index }">
                          <span v-if="index === 0">{{ item.name }} </span>
                          <span
                            v-if="index === 1"
                            class="grey--text text-caption"
                          >
                            (+{{ filter_provider.length - 1 }} )
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
                      </v-autocomplete></v-col
                    >
                    <v-col>
                      <v-select
                        v-model="filter_office"
                        :items="d_offices"
                        item-text="name"
                        item-value="id"
                        label="Фильтр офис"
                        outlined
                        rounded
                        multiple
                      >
                      </v-select
                    ></v-col>
                    <v-col>
                      <v-btn
                        outlined
                        rounded
                        @click="exportXlsx"
                        class="border"
                      >
                        <v-icon left> mdi-file-excel </v-icon>
                        Скачать таблицу
                      </v-btn></v-col
                    >
                    <v-col>
                      <v-data-footer
                        :pagination="pagination"
                        :options="options"
                        @update:options="updateOptions"
                        :items-per-page-options="pages"
                        items-per-page-text=""
                      />
                    </v-col>
                  </v-row>
                </template>
              </v-data-table>
            </v-col>
          </v-row>
        </v-container>
      </v-tab-item>
    </v-tabs-items>
    <ConfirmDlg ref="confirm" />
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="headline">Редактировать сообщение</span>
        </v-card-title>

        <v-card-text>
          <v-container>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.message"
                  label="Название в базе"
                  :rules="[validateName]"
                  rows="1"
                  @input="checkName"
                  :error-messages="errorMessages.length ? errorMessages : []"
                  :disabled="editedItem.message == undefined"
                ></v-text-field>
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="editedItem.sum"
                  label="Сумма"
                ></v-text-field>
              </v-col>
              <v-col cols="6">
                <v-radio-group v-model="editedItem.cp" row>
                  <v-radio label="CPL" value="L"></v-radio>
                  <v-radio label="CPA" value="A"></v-radio>
                </v-radio-group>
              </v-col>
              <v-col cols="12" v-if="editedItem.message">
                <v-select
                  v-model="editedItem.provider_id"
                  :items="providers"
                  item-text="name"
                  item-value="id"
                  label="Провайдер"
                  @change="setBaer()"
                ></v-select>
                <v-select
                  v-if="baers.length"
                  v-model="editedItem.baer"
                  :items="baers"
                  label="Баер"
                  clearable="clearable"
                ></v-select>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue darken-1" text @click="close"> Отмена </v-btn>
          <v-btn color="blue darken-1" text @click="save"> Сохранить </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-navigation-drawer width="450" v-model="drawer" fixed temporary right>
      <selectUsers :user="$attrs.user" @getUserIds="p_user_ids" />
    </v-navigation-drawer>
  </div>
</template>

<script>
import XLSX from "xlsx";
import axios from "axios";
import importBTC from "./importBTC";
import importxlsx from "./importxlsx";
import logtel from "../manager/logtel";
import selectUsers from "./UI/selectUsers";
import _ from "lodash";
export default {
  name: "ImportCSV",
  data: () => ({
    true: true,
    i_geos: [],
    tabimport: "files",
    true: true,
    nameExists: false,
    errorMessages: [],
    hmmonth: 6,
    pages: [100, 200, 500, -1],
    search: "",
    user_ids: [],
    clearable: true,
    takedates: true,
    dateFrom: false,
    dateTo: false,
    dateProps: { locale: "ru-RU", format: "24hr" },
    datetimeFrom: new Date(new Date().setDate(new Date().getDate() - 3))
      .toISOString()
      .substring(0, 10),
    datetimeTo: new Date().toISOString().substring(0, 10),
    filter_import_provider: [],
    i_providers: [],
    list_email: "",
    file_emails: [],
    in_db: [],
    out_db: [],
    message: "",
    snackbar: false,
    loading: false,
    activeRequests: 0,
    userid: null,
    users: [],
    providers: [],
    statuses: [],
    imports: [],
    selectedStatus: 8,
    selectedProvider: 0,
    related_user: [],
    selected: [],
    selectedTop: [],
    importSelected: [],
    files: [],
    search: "",
    filtertel: "",

    headers_leads: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Поставщик", value: "provider" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Оффис", value: "office" },
      { text: "Менеджер", value: "user" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status" },
      { text: "Изменён", value: "date_updated" },
      { text: "Название базы", value: "load_mess" },
      { text: "Депозит", value: "deposit" },
      { text: "Сообщение", value: "text" },
      { text: "Звонков", value: "qtytel" },
      { text: "ПЕРЕЗВОН", value: "ontime" },
      { text: "Pending", value: "pending" },
      { text: "Депозит", value: "depozit" },
    ],
    headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Тел.", align: "start", value: "tel" },
      { text: "Афилятор", value: "afilyator" },
    ],
    import_headers: [
      { text: "Дата", value: "start", sortable: false },
      { text: "Загрузил", value: "user", sortable: false, align: "center" },
      { text: "Сумма", value: "sum", align: "center", class: "w150" },
      { text: "Поставщик", value: "provider", sortable: false },
      // { text: "L/A", value: "cp", sortable: false },
      {
        text: "Коментарий",
        value: "message",
        sortable: false,
      },
      {
        text: "Total",
        value: "hm",
        cellСlass: "fz17",
        align: "center",
        class: "w150",
      },
      { text: "ГЕО", value: "geo", align: "center", class: "w150" },

      {
        text: "NEW",
        value: "hmnew",
        class: "new",
        cellClass: "new fz17",
        align: "center",
      },
      {
        text: "ReNew",
        value: "hmrenew",
        class: "renew",
        cellClass: "renew fz17",
        align: "center",
      },
      {
        text: "CallBack",
        value: "hmcb",
        class: "callback",
        cellClass: "callback fz17",
        align: "center",
      },
      {
        text: "Deposit",
        value: "hmdp",
        class: "deposit",
        cellClass: "deposit fz17",
        align: "center",
      },
      {
        text: "Pending",
        value: "hmpnd",
        class: "pending",
        cellClass: "pending fz17",
        align: "center",
      },
      {
        text: "Potential",
        value: "hmpot",

        class: "potential",
        cellClass: "potential fz17",
        align: "center",
      },
      {
        text: "NoAns",
        value: "hmnoans",
        class: "noans",
        cellClass: "noans fz17",
        align: "center",
      },
      {
        text: "NotInt",
        value: "hmnointerest",
        class: "nointerest",
        cellClass: "nointerest fz17",
        align: "center",
      },
      { text: "", value: "id", align: "center", sortable: false },
    ],
    providerHeaders: [
      {
        text: "Provider",
        value: "provider",
        width: "100px",
        cellClass: "fz17",
      },
      { text: "L|A", value: "", width: "100px", align: "center" },
      { text: "Sum", value: "sum", width: "100px", align: "center" },
      {
        text: "Total",
        value: "hm",
        width: "100px",
        cellClass: "fz17",
        align: "center",
      },
      {
        text: "NEW",
        value: "hmnew",
        width: "100px",
        class: "new",
        cellClass: "new fz17",
        align: "center",
      },
      {
        text: "ReNEW",
        value: "hmrenew",
        width: "100px",
        class: "renew",
        cellClass: "renew fz17",
        align: "center",
      },
      {
        text: "CallBAck",
        value: "hmcb",
        width: "100px",
        class: "callback",
        cellClass: "callback fz17",
        align: "center",
      },
      {
        text: "Deposit",
        value: "hmdp",
        width: "100px",
        class: "deposit",
        cellClass: "deposit fz17",
        align: "center",
      },
      {
        text: "Pending",
        value: "hmpnd",
        width: "100px",
        class: "pending",
        cellClass: "pending fz17",
        align: "center",
      },
      {
        text: "Potential",
        value: "hmpot",
        width: "100px",
        class: "potential",
        cellClass: "potential fz17",
        align: "center",
      },
      {
        text: "NoAnswer",
        value: "hmnoans",
        width: "100px",
        class: "noans",
        cellClass: "noans fz17",
        align: "center",
      },
      {
        text: "NotInt",
        value: "hmnointerest",
        width: "100px",
        class: "nointerest",
        cellClass: "nointerest fz17",
        align: "center",
      },
    ],
    dateHeaders: [
      {
        text: "Date",
        value: "date",
        width: "100px",
        cellClass: "pl-5",
      },
      { text: "L|A", value: "", width: "100px", align: "center" },
      {
        text: "Sum",
        value: "sum",
        width: "100px",
        align: "center",
      },
      {
        text: "Total",
        value: "hm",
        width: "100px",
        cellClass: "fz17",
        align: "center",
      },
      {
        text: "NEW",
        value: "hmnew",
        width: "100px",
        class: "new",
        cellClass: "new fz17",
        align: "center",
      },
      {
        text: "ReNEW",
        value: "hmrenew",
        width: "100px",
        class: "renew",
        cellClass: "renew fz17",
        align: "center",
      },
      {
        text: "CB",
        value: "hmcb",
        width: "100px",
        class: "callback",
        cellClass: "callback fz17",
        align: "center",
      },
      {
        text: "DP",
        value: "hmdp",
        width: "100px",
        class: "deposit",
        cellClass: "deposit fz17",
        align: "center",
      },
      {
        text: "PND",
        value: "hmpnd",
        width: "100px",
        class: "pending",
        cellClass: "pending fz17",
        align: "center",
      },
      {
        text: "POT",
        value: "hmpot",
        width: "100px",
        class: "potential",
        cellClass: "potential fz17",
        align: "center",
      },
      {
        text: "NoAnswer",
        value: "hmnoans",
        width: "100px",
        class: "noans",
        cellClass: "noans fz17",
        align: "center",
      },
      {
        text: "NotInterest",
        value: "hmnointerest",
        width: "100px",
        class: "nointerest",
        cellClass: "nointerest fz17",
        align: "center",
      },
    ],
    geoHeaders: [
      { text: "", value: "", width: "1px" },
      { text: "GEO", value: "geo", width: "100px" },
      { text: "Sum", value: "sum", width: "100px", align: "center" },
      { text: "L|A", value: "cp", width: "100px", align: "center" },
      {
        text: "NEW",
        value: "hmnew",
        width: "100px",
        class: "new",
        cellClass: "new fz17",
        align: "center",
      },
      {
        text: "ReNEW",
        value: "hmrenew",
        width: "100px",
        class: "renew",
        cellClass: "renew fz17",
        align: "center",
      },
      {
        text: "CB",
        value: "hmcb",
        width: "100px",
        class: "callback",
        cellClass: "callback fz17",
        align: "center",
      },
      {
        text: "DP",
        value: "hmdp",
        width: "100px",
        class: "deposit",
        cellClass: "deposit fz17",
        align: "center",
      },
      {
        text: "PND",
        value: "hmpnd",
        width: "100px",
        class: "pending",
        cellClass: "pending fz17",
        align: "center",
      },
      {
        text: "POT",
        value: "hmpot",
        width: "100px",
        class: "potential",
        cellClass: "potential fz17",
        align: "center",
      },
      {
        text: "NoAnswer",
        value: "hmnoans",
        width: "100px",
        class: "noans",
        cellClass: "noans fz17",
        align: "center",
      },
      {
        text: "NotInterest",
        value: "hmnointerest",
        width: "100px",
        class: "nointerest",
        cellClass: "nointerest fz17",
        align: "center",
      },
      {
        text: "HM",
        value: "hm",
        width: "100px",

        align: "center",
      },
    ],
    providerSummaries: [],
    // need del
    import_provider_headers: [
      { text: "Дата", value: "start", cellClass: "t1" },
      {
        text: "Поставщик",
        value: "provider",
        sortable: false,
        width: "150px",
      },
      { text: "Сумма", value: "sum", sortable: false, width: 100 },
      { text: "L/A", value: "cp", sortable: false, width: "53px" },
      {
        text: "NEW",
        value: "hmnew",
        sortable: false,
        class: "new",
        cellClass: "new fz17",
        align: "center",
        width: "150px",
      },
      {
        text: "CallBack",
        value: "hmcb",
        sortable: false,
        class: "callback",
        cellClass: "callback fz17",
        align: "center",
        width: "150px",
      },
      {
        text: "Deposit",
        value: "hmdp",
        sortable: false,
        class: "deposit",
        cellClass: "deposit fz17",
        align: "center",
        width: "150px",
      },
      {
        text: "Pending",
        value: "hmpnd",
        sortable: false,
        class: "pending",
        cellClass: "pending fz17",
        align: "center",
        width: "150px",
      },
      {
        text: "Potential",
        value: "hmpot",
        sortable: false,
        class: "potential",
        cellClass: "potential fz17",
        align: "center",
        width: "150px",
      },
      {
        text: "Total",
        value: "hm",
        sortable: false,
        align: "center",
        width: "150px",
      },
      { text: "GEO", value: "geo", sortable: false },
      // { text: "", value: "id" },
    ],
    duplicate_leads_headers: [
      { text: "Имя", value: "name" },
      { text: "Email", value: "email" },
      { text: "Телефон.", align: "start", value: "tel" },
      { text: "Поставщик", value: "provider_name" },
      { text: "Афилятор", value: "afilyator" },
      { text: "Оффис", value: "office_name" },
      { text: "Менеджер", value: "user_name" },
      { text: "Создан", value: "date_created" },
      { text: "Статус", value: "status_name" },
      { text: "Изменён", value: "date_updated" },
      { text: "Название базы", value: "load_mess" },
      { text: "Депозит", value: "deposit" },
      { text: "Сообщение", value: "text" },
      { text: "Звонков", value: "qtytel" },
      { text: "ПЕРЕЗВОН", value: "ontime" },
      { text: "Pending", value: "pending" },
      { text: "Депозит", value: "depozit" },
    ],
    importsProvLeads: [],
    duplicate_leads: [],
    parse_header: [],
    parse_csv: [],
    sortOrders: {},
    sortKey: "tel",
    tab: 0,
    Statuses: [],
    filterStatus: [],
    filter_status: [],
    filter_provider: [],
    filter_office: [],
    filterStatusTabl: [],
    filterOfficeTabl: [],
    filter_geo: [],
    resetStatus: [],
    leads: [],
    email_tel: "email",
    printfield: [
      "tel",
      "name",
      "created",
      "updated",
      "status_id",
      "status_name",
      "email",
      "user_name",
      "provider_name",
      "afilyator",
      "office_name",
    ],
    printfields: [],
    exportfield: [
      "name",
      "email",
      "tel",
      "status",
      "date_created",
      "updated_at",
      "user",
      "provider",
      "afilyator",
      "office",
      "text",
    ],
    exportfields: [
      "name",
      "email",
      "tel",
      "status",
      "date_created",
      "updated_at",
      "user",
      "provider",
      "afilyator",
      "office",
      "text",
    ],
    item: {},
    dialog: false,
    editedItem: {
      id: 0,
      // start:'',
      // end:'',
      provider: "",
      baer: "",
      // provider_id:'',
      // user:'',
      message: "",
      // group_id:'',
      // load_key:'',
      sum: 0,
      cp: "L",
    },
    editedIndex: -1,
    usersStatuses: [],
    drawer: false,
    history: [],
    historyStatus: [],
    holdLidsUsers: [],
    baers: [],
    lidsByOffice: [],
    offices: [],
    redistributeOffice: null,
    apigroup: [],
    expanded: [],
    expandedate: [],
    d_statuses: [],
    d_providers: [],
    d_offices: [],
  }),
  watch: {
    selectedProvider: function (newval) {
      this.users = [];
      this.related_user = [];

      const rel_user = this.providers.find(
        (p) => p.id == newval
      ).related_users_id;
      if (rel_user.length > 2) {
        this.related_user = JSON.parse(rel_user);
        this.getUsers();
      }
    },
  },

  mounted() {
    this.getProviders();
    //this.ImportedProvLids();
    this.getOffices();
    this.getStatuses();
  },
  computed: {
    filteredLeads() {
      return this.leads.filter((i) => {
        return (
          (!this.filterStatusTabl.length ||
            this.filterStatusTabl.includes(i.status_id)) &&
          (!this.filterOfficeTabl.length ||
            this.filterOfficeTabl.includes(i.office))
        );
      });
    },
    filteredProviderSummaries() {
      // Assuming providerSummaries is populated from importsProvLeads
      const providerSummaries = this.importsProvLeads.map((lead) => {
        // Transform lead data to provider summary format
        return {
          provider_id: lead.provider_id,
          dates: lead.dates.map((date) => ({
            date: date.date,
            geo: date.geo.map((geo) => ({
              geo: geo.geo,
              // other geo properties...
            })),
          })),
        };
      });

      return providerSummaries
        .filter((provider) => {
          // Check if the provider matches the selected providers
          const providerMatch =
            this.filter_import_provider.length === 0 ||
            this.filter_import_provider.includes(provider.provider_id);

          if (!providerMatch) {
            return false;
          }

          // Filter the dates to include only those with the selected geos
          const filteredDates = provider.dates
            .map((date) => {
              const filteredGeos = date.geo.filter((geo) =>
                this.filter_geo.includes(geo.geo)
              );
              return filteredGeos.length > 0
                ? { ...date, geo: filteredGeos }
                : null;
            })
            .filter((date) => date !== null);

          return filteredDates.length > 0
            ? { ...provider, dates: filteredDates }
            : null;
        })
        .filter((provider) => provider !== null);
    },

    filteredItems() {
      let reg = new RegExp("^" + this.filtertel);
      return this.parse_csv.filter((i) => {
        return !this.filtertel || reg.test(i.tel);
      });
    },
    filter_imports() {
      return this.imports.filter((i) => {
        return (
          (this.filter_import_provider.length == 0 ||
            this.filter_import_provider.includes(i.provider_id)) &&
          (!this.filter_geo.length || this.filter_geo.includes(i.geo))
        );
      });
    },
    filter_importsProvLeads() {
      if (this.importsProvLeads.length) {
        let prov = this.importsProvLeads.filter((i) => {
          return (
            (this.filter_import_provider.length == 0 ||
              this.filter_import_provider.includes(i.provider_id)) &&
            (!this.filter_geo.length || this.filter_geo.includes(i.geo))
          );
        });
        return this.provsumm(prov);
      }
    },
    filtereduplicate_leads() {
      return this.duplicate_leads.filter((i) => {
        return (
          (this.filter_status.length == 0 ||
            this.filter_status.includes(i.status_id)) &&
          (this.filter_provider.length == 0 ||
            this.filter_provider.includes(i.provider_id)) &&
          (this.filter_office.length == 0 ||
            this.filter_office.includes(i.office_id))
        );
      });
    },
  },
  methods: {
    setTop() {
      const vm = this;
      let data = {
        data: vm.selectedTop,
      };
      data.data = data.data.map((el) => {
        return { id: el.id, top: 1 };
      });
      axios.post("api/setTop", data).then((response) => {
        console.log("Сохранено");
        vm.selectedTop = [];
      });
    },
    provsumm(prov) {
      const providers = {};
      const office_id = this.$attrs.user.office_id;

      prov.map((row) => {
        if (!row.hm_json) return;
        const hmData = JSON.parse(row.hm_json).find(
          (hm) => hm.office_id === office_id
        );
        if (!hmData) return;

        if (!providers[row.provider]) {
          providers[row.provider] = {
            id: row.provider_id, // Add a unique identifier
            provider: row.provider,
            hmnew: 0,
            hmrenew: 0,
            hmcb: 0,
            hmdp: 0,
            hmpnd: 0,
            hmpot: 0,
            hmnoans: 0,
            hmnointerest: 0,
            hm: 0,
            sum: 0,
            dates: {},
          };
        }

        providers[row.provider].hmnew += parseInt(hmData.hmnew);
        providers[row.provider].hmrenew += parseInt(hmData.hmrenew);
        providers[row.provider].hmcb += parseInt(hmData.hmcb);
        providers[row.provider].hmdp += parseInt(hmData.hmdp);
        providers[row.provider].hmpnd += parseInt(hmData.hmpnd);
        providers[row.provider].hmpot += parseInt(hmData.hmpot);
        providers[row.provider].hmnoans += parseInt(hmData.hmnoans);
        providers[row.provider].hmnointerest += parseInt(hmData.hmnointerest);
        providers[row.provider].hm += parseInt(hmData.hm);
        providers[row.provider].sum += parseInt(row.sum);

        if (!providers[row.provider].dates[row.date]) {
          providers[row.provider].dates[row.date] = {
            date: row.date,
            id: row.provider_id + row.date,
            hmnew: 0,
            hmrenew: 0,
            hmcb: 0,
            hmdp: 0,
            hmpnd: 0,
            hmpot: 0,
            hmnoans: 0,
            hmnointerest: 0,
            hm: 0,
            sum: 0,
            geo: [],
          };
        }

        providers[row.provider].dates[row.date].hmnew += parseInt(hmData.hmnew);
        providers[row.provider].dates[row.date].hmrenew += parseInt(
          hmData.hmrenew
        );
        providers[row.provider].dates[row.date].hmcb += parseInt(hmData.hmcb);
        providers[row.provider].dates[row.date].hmdp += parseInt(hmData.hmdp);
        providers[row.provider].dates[row.date].hmpnd += parseInt(hmData.hmpnd);
        providers[row.provider].dates[row.date].hmpot += parseInt(hmData.hmpot);
        providers[row.provider].dates[row.date].hmnoans += parseInt(
          hmData.hmnoans
        );
        providers[row.provider].dates[row.date].hmnointerest += parseInt(
          hmData.hmnointerest
        );
        providers[row.provider].dates[row.date].hm += parseInt(hmData.hm);
        providers[row.provider].dates[row.date].sum += parseInt(row.sum);

        providers[row.provider].dates[row.date].geo.push({
          geo: row.geo,
          cp: row.cp,
          start: row.date,
          provider_id: row.provider_id,
          hmnew: parseInt(hmData.hmnew),
          hmrenew: parseInt(hmData.hmrenew),
          hmcb: parseInt(hmData.hmcb),
          hmdp: parseInt(hmData.hmdp),
          hmpnd: parseInt(hmData.hmpnd),
          hmpot: parseInt(hmData.hmpot),
          hmnoans: parseInt(hmData.hmnoans),
          hmnointerest: parseInt(hmData.hmnointerest),
          hm: parseInt(hmData.hm),
          sum: parseInt(row.sum),
          id: row.id,
        });
      });

      return Object.values(providers).map((provider) => {
        provider.dates = Object.values(provider.dates);
        return provider;
      });
    },
    filterOfficeStatus(office, status) {
      if (office == 0) {
        if (this.filterOfficeTabl.length > 0) {
          this.filterStatusTabl = [];
          this.filterOfficeTabl = [];
        }
      } else {
        if (this.filterOfficeTabl.length == 0) {
          this.filterStatusTabl = [];
          this.filterOfficeTabl = [];
        }
        if (!this.filterOfficeTabl.includes(office)) {
          this.filterStatusTabl = [];
          this.filterOfficeTabl = [];
          this.filterOfficeTabl.push(office);
        }
      }
      if (this.filterStatusTabl.includes(status)) {
        this.filterStatusTabl = this.filterStatusTabl.filter((s) => {
          return s != status;
        });
      } else {
        this.filterStatusTabl.push(status);
      }
      if (this.filterStatusTabl.length == 0) {
        this.filterOfficeTabl = [];
      }
      console.log(office, status);
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
    changeFilterStatusClick(status_id) {
      this.filterStatusTabl = [];
      this.filterStatusTabl.push(status_id);
    },
    changeFilterStatus(status_id) {
      if (this.filter_status.includes(status_id)) {
        const index = this.filter_status.indexOf(status_id);
        this.filter_status.splice(index, 1);
      } else {
        this.filter_status.push(status_id);
      }
    },
    sumField(item, tab) {
      return tab.reduce((a, b) => parseInt(a) + parseInt(b[item] || 0), 0);
    },
    importCallc() {
      const vm = this;
      axios
        .get("api/importCallc")
        .then(function (response) {
          vm.getImports();
          vm.ImportedProvLids();
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    filterAPI() {
      this.apigroup = _.groupBy(this.filter_importsProvLeads, "group");
    },
    clickrowd(item, row) {
      if (!row.isExpanded) {
        this.expanded = [item];
      } else {
        this.expanded = [];
      }
    },
    closeAll() {
      Object.keys(this.$refs).forEach((k) => {
        //console.log(this.$refs[k]);
        if (this.$refs[k] && this.$refs[k].$attrs["data-open"]) {
          this.$refs[k].$el.click();
        }
      });
    },
    setBaer() {
      const baer = this.providers.find((p) => {
        return p.id == this.editedItem.provider_id;
      }).baer;
      this.baers = [];
      if (baer && baer != "") {
        this.baers = baer.split(";");
      }
    },
    getHistory() {
      const self = this;
      self.loading = true;
      this.activeRequests++;
      let data = {};
      data.id = self.item.id;
      data.message = self.item.message;
      axios
        .post("api/getHistory", data)
        .then(function (response) {
          self.history = response.data.history;
          self.historyStatus = response.data.statuses;
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    redistributeLids() {
      const self = this;
      let data = {};
      self.activeRequests++;
      self.loading = true;
      data.lid_ids = this.lidsByOffice
        .find((f) => {
          return f.name == self.redistributeOffice;
        })
        .lids.filter((f) => {
          return (
            !this.resetStatus.length || this.resetStatus.includes(f.status_id)
          );
        })
        .map((l) => l.id);
      data.usersIds = this.user_ids;
      data.resetStatus = this.resetStatus;
      data.id = this.item.id;
      data.provider_id = this.item.provider_id;
      data.message = this.item.message;
      data.start = this.item.start;
      data.geo = this.item.geo;
      if (this.item.end != undefined) {
        data.end = this.item.end;
      }
      axios
        .post("api/redistributeLids", data)
        .then(function (response) {
          self.message = `Переназначение успешно выполнено<br>Для Офиса: ${self.redistributeOffice}`;

          self.importSelected = [];
          self.lidsByOffice = [];

          (self.redistributeOffice = null), (self.user_ids = []);
          self.snackbar = true;
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    p_user_ids(user_ids) {
      const self = this;
      this.user_ids = user_ids;
      if (this.user_ids.length == 0) {
        this.user_ids = [this.$attrs.user.id];
      }
      this.drawer = false;
      if (this.redistributeOffice) {
        this.redistributeLids();
        return;
      }
      this.activeRequests++;
      self.loading = true;

      let data = {};
      data.importsIdsm = this.importSelected.map(
        ({ id, message, provider_id, start, geo }) => ({
          id,
          message,
          provider_id,
          start,
          geo,
        })
      );
      data.usersIds = this.user_ids;
      data.resetStatus = this.resetStatus;

      axios
        .post("api/redistribute", data)
        .then(function (response) {
          self.message = `Переназначение успешно выполнено<br>Для статусов: ${self.statuses
            .filter((s) => {
              return data.resetStatus.includes(s.id);
            })
            .map(({ name }) => {
              return name;
            })
            .toString()}<br>Файлы: ${self.importSelected.map(({ message }) => {
            return message;
          })}`;

          self.importSelected = [];

          self.lidsByOffice = [];

          (self.redistributeOffice = null), (self.user_ids = []);
          self.snackbar = true;
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    ImportedProvLids() {
      const self = this;
      self.activeRequests++;
      self.loading = true;
      let datefrom = self.datetimeFrom,
        dateto = self.datetimeTo;
      if (self.takedates == 0) {
        datefrom = 0;
        dateto = 0;
      }

      axios
        .get("api/ImportedProvLids/" + datefrom + "/" + dateto)
        .then(function (response) {
          self.importsProvLeads = response.data;

          if (self.importsProvLeads) {
            self.importsProvLeads.map((i) => {
              if (!self.i_geos.includes(i.geo)) {
                self.i_geos.push(i.geo);
              }
            });
            self.i_geos.sort();
            // self.importsProvLeads = self.importsProvLeads.map((ip) => {
            //   ip.group = ip.date + " " + ip.provider;
            //   return ip;
            // });
            self.importsProvLeads = _.orderBy(
              self.importsProvLeads,
              "date",
              "desc"
            );
            self.apigroup = _.groupBy(self.filter_importsProvLeads, "group");
            let a_prov = _.uniq(
              _.map(self.importsProvLeads, (el) => {
                return el.provider_id;
              })
            );
            self.i_providers = _.union(
              self.i_providers,
              self.providers.filter((i) => {
                return a_prov.includes(i.id);
              })
            );
          }
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
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
    exportImportedXlsx() {
      const self = this;
      let flids;
      if (self.filterStatus.length > 0) {
        flids = _.filter(self.leads, (el) => {
          return self.filterStatus.includes(el.status_id);
          //"created", "updated","user_name", "provider_name", "afilyator", "office_name"
        }).map(
          ({
            name,
            email,
            tel,
            status,
            date_created,
            updated_at,
            user,
            provider,
            afilyator,
            office,
            text,
          }) => ({
            name,
            email,
            tel,
            status,
            date_created,
            updated_at,
            user,
            provider,
            afilyator,
            office,
            text,
          })
        );
      } else {
        flids = self.leads.map(
          ({
            name,
            email,
            tel,
            status,
            date_created,
            updated_at,
            user,
            provider,
            afilyator,
            office,
            text,
          }) => ({
            name,
            email,
            tel,
            status,
            date_created,
            updated_at,
            user,
            provider,
            afilyator,
            office,
            text,
          })
        );
      }

      const newLeads = flids.map((obj) => {
        const newObj = {};
        self.exportfields.forEach((prop) => {
          newObj[prop] = obj[prop];
        });
        return newObj;
      });

      var wb = XLSX.utils.book_new(); // make Workbook of Excel

      window["list"] = XLSX.utils.json_to_sheet(newLeads);
      XLSX.utils.book_append_sheet(wb, window["list"], "imported leads");

      // export Excel file
      XLSX.writeFile(
        wb,
        "imported_" + self.item.start.replaceAll(/ |:/g, "_") + ".xlsx"
      );
    },
    exportXlsx() {
      const self = this;
      let unique = [];
      // const obj = _.groupBy(self.filteredItems, "status");
      // const lidsByStatus = Array.from(Object.keys(obj), (k) => [
      //   `${k}`,
      //   obj[k],
      // ]);
      var wb = XLSX.utils.book_new(); // make Workbook of Excel
      var wbuni = XLSX.utils.book_new();

      if (self.email_tel === "tel") {
        unique = self.out_db.map((i) => ({
          id: "",
          created: "",
          updated: "",
          status_id: "",
          status_name: "",
          name: "name" + i,
          email: i + "@unique.com",
          tel: i,
        }));
      } else {
        unique = self.out_db.map((i) => ({ email: i }));
      }
      //
      // 9-callback,10-deposit,20-pending
      unique = unique.filter((el) => {
        return ![9, 10, 20].includes(el["status_id"]);
      });
      //if (self.email_tel === "tel") {
      let con = [];
      let a_bad_tel = [];
      self.filtereduplicate_leads.map((t) => {
        if ([9, 10, 11, 20, 21, 22, 23].includes(t.status_id))
          a_bad_tel.push(t["tel"]);
      });

      const dup_not = self.filtereduplicate_leads.filter((dd) => {
        return !a_bad_tel.includes(dd["tel"]);
      });
      // const dup_call = self.filtereduplicate_leads.filter((dd) => {
      //   return (
      //     dd.status_id == 9 &&
      //     (Date.now() - Date.parse(dd.updated)) / (60 * 60 * 24 * 1000) > 21
      //   );
      // });
      con = con.concat(unique, dup_not); //, dup_call
      window["con"] = XLSX.utils.json_to_sheet(con);
      XLSX.utils.book_append_sheet(wb, window["con"], "CHECK_TO_UPLOAD");
      //}

      window["unique"] = XLSX.utils.json_to_sheet(unique);
      XLSX.utils.book_append_sheet(wb, window["unique"], "unique");
      XLSX.utils.book_append_sheet(wbuni, window["unique"], "unique");

      window["list"] = XLSX.utils.json_to_sheet(self.filtereduplicate_leads);
      XLSX.utils.book_append_sheet(wb, window["list"], "duplicate");

      //if (self.email_tel === "tel") {
      // - 3ая -  все дубли со статусом депозит
      const dup_dep = self.filtereduplicate_leads.filter((dd) => {
        return dd.status_id == 10;
      });
      window["dup_dep"] = XLSX.utils.json_to_sheet(dup_dep);
      XLSX.utils.book_append_sheet(wb, window["dup_dep"], "duplicate_doposit");
      // - 4ая - все дубли со статусом колбек
      const dup_cb = self.filtereduplicate_leads.filter((dd) => {
        return dd.status_id == 9;
      });
      window["dup_cb"] = XLSX.utils.json_to_sheet(dup_cb);
      XLSX.utils.book_append_sheet(wb, window["dup_cb"], "duplicate_callback");
      // - 5ая - все дубли которые имеют статус -  deposit, callback, trash, badphone моложе трех недель)
      const dup_3week = self.filtereduplicate_leads.filter((dd) => {
        return (
          [9, 10, 11, 23].includes(dd.status_id) &&
          (Date.now() - Date.parse(dd.created)) / (60 * 60 * 24 * 1000) < 21
        );
      });
      window["dup_3week"] = XLSX.utils.json_to_sheet(dup_3week);
      XLSX.utils.book_append_sheet(wb, window["dup_3week"], "duplicate_3week");
      //}

      // export Excel file
      XLSX.writeFile(
        wb,
        unique.length +
          "-un_" +
          self.in_db.length +
          "-dup" +
          "_" +
          new Date().toDateString() +
          ".xlsx"
      ); // name of the file is 'book.xlsx'
      if (unique.length) {
        XLSX.writeFile(
          wbuni,
          unique.length + "-un_" + new Date().toDateString() + ".xlsx"
        );
      }
    },
    checkEmails() {
      let vm = this;
      vm.activeRequests++;
      vm.loading = true;
      vm.snackbar = false;
      vm.message = "";
      vm.duplicate_leads = [];
      vm.in_db = [];
      vm.out_db = [];
      let data = {};

      data.emails = vm.list_email
        .replace(/[\r]/gm, "")
        .replaceAll(" ", "")
        .split("\n");

      data.check = 1;
      data.email_tel = vm.email_tel;
      data.hmmonth = vm.hmmonth;
      axios
        .post("api/checkEmails", data)
        .then(function (res) {
          vm.in_db = res.data.emails.filter((n) => n);

          vm.out_db = [
            ...new Set(
              data.emails.filter((i) => !vm.in_db.includes(i.toLowerCase()))
            ),
          ];
          vm.out_db = vm.out_db.filter((e) => e != "");
          vm.message =
            "Уникальных: " +
            vm.out_db.length +
            "<br>Дубликатов: " +
            vm.in_db.length;
          vm.snackbar = true;
          vm.duplicate_leads = res.data.leads;
          vm.duplicate_leads = vm.duplicate_leads.map((dl) => {
            dl.date_created = dl.created_at.substring(0, 10);
            dl.date_updated = dl.updated_at.substring(0, 10);
            return dl;
          });
          let a_status = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.status_id;
            })
          );
          vm.d_statuses = vm.statuses.filter((i) => {
            return a_status.includes(i.id);
          });
          let a_hm = _.groupBy(vm.duplicate_leads, "status_id");
          vm.d_statuses.map((el) => {
            el.hm = a_hm[el.id].length;
          });

          let a_prov = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.provider_id;
            })
          );
          vm.d_providers = vm.providers.filter((i) => {
            return a_prov.includes(i.id);
          });
          let a_offices = _.uniq(
            _.map(vm.duplicate_leads, (el) => {
              return el.office_id;
            })
          );
          vm.d_offices = vm.offices.filter((i) => {
            return a_offices.includes(i.id);
          });

          vm.list_email = "";
          vm.files = [];
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    filterStatuses() {
      const self = this;
      let stord = this.leads;
      let groupByOffice = [];
      self.Statuses = [];
      self.lidsByOffice = [];
      groupByOffice = Object.entries(_.groupBy(this.leads, "office_id"));
      groupByOffice.forEach((a_office) => {
        let nameoffice = "";
        try {
          nameoffice = self.offices.find((o) => {
            return o.id == a_office[0];
          }).name;
        } catch (error) {
          console.log(error);
        }

        const lids = Object.entries(_.groupBy(a_office[1], "status"));
        let statuses = [];
        lids.map(function (i) {
          //i[0]//name
          //i[1]//array
          let el = self.statuses.find((s) => s.name == i[0]);
          statuses.push({
            id: el.id,
            name: i[0],
            hm: i[1].length,
            order: el.order,
            color: el.color,
          });
        });
        statuses = _.orderBy(statuses, "order");
        self.lidsByOffice.push({
          name: nameoffice,
          lids: a_office[1],
          statuses: statuses,
        });
      });
      self.holdLidsUsers = Object.entries(_.groupBy(self.leads, "user"));
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
    usercolor(user) {
      return user.role_id == 2 ? "green" : "blue";
    },
    putSelectedLidsDB() {
      let self = this;
      self.activeRequests++;
      self.loading = true;
      let send = {};
      send.user_id = this.userid;
      send.message = this.message;
      send.provider_id = this.selectedProvider;
      if (this.selectedStatus !== 0) {
        send.status_id = this.selectedStatus;
      }
      if (this.$refs.datatable.items.length > 0) {
        if (this.selected.legth) send.data = this.selected;
        else send.data = this.$refs.datatable.items;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            // console.log(response);
            if (self.selected.length) {
              self.parse_csv = self.parse_csv.filter(
                (ar) => !self.selected.find((rm) => rm.tel === ar.tel)
              );
            } else {
              self.parse_csv = [];
            }
            self.selected = [];
            self.getUsers();

            self.files = [];
            // save to imports db
            //======================
            let info = {};

            info.start = response.data.date_start
              .substring(0, 19)
              .replace("T", " ");
            info.end = response.data.date_end
              .substring(0, 19)
              .replace("T", " ");
            info.geo = response.data.geo;
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports", info)
              .then(function (response) {})
              .catch(function (error) {
                console.log(error);
              });
          })
          .catch(function (error) {
            console.log(error);
          })
          .finally(() => {
            this.activeRequests--;
            if (this.activeRequests === 0) {
              this.loading = false;
            }
          });
      } else if (
        (this.search !== "" || this.filtertel !== "") &&
        this.$refs.datatable.$children[0].filteredItems.length > 0
      ) {
        send.data = this.$refs.datatable.$children[0].filteredItems;
        axios
          .post("api/Lid/newlids", send)
          .then(function (response) {
            self.parse_csv = self.parse_csv.filter(
              (ar) =>
                !self.$refs.datatable.$children[0].filteredItems.find(
                  (rm) => rm.tel === ar.tel
                )
            );
            // self.getUsers();
            self.search = "";
            self.filtertel = "";

            // save to imports db
            //======================
            let info = {};

            info.start = response.data.date_start
              .substring(0, 19)
              .replace("T", " ");
            info.end = response.data.date_end
              .substring(0, 19)
              .replace("T", " ");
            info.geo = response.data.geo;
            info.provider_id = self.selectedProvider;
            info.user_id = self.$attrs.user.id;
            info.message = self.message;

            axios
              .post("api/imports", info)
              .then(function (response) {
                // console.log(response);
              })
              .catch(function (error) {
                console.log(error);
              });
          })
          .catch(function (error) {
            console.log(error);
          })
          .finally(() => {
            this.activeRequests--;
            if (this.activeRequests === 0) {
              this.loading = false;
            }
          });
      }
      if (this.parse_csv.length == 0) {
        this.files = [];
        this.selectedProvider = "";
      }
      this.userid = null;
      this.$refs.radiogroup.lazyValue = null;
      this.getUsers();
    },

    getLog(i, row) {
      this.$refs.datatablelids.expansion = {};

      row.expand(!row.isExpanded);
    },
    toggleExpandDate(item) {
      const itemId = item.id; // Use the unique identifier
      const expandedItem = this.expandedate.find(
        (expandedItem) => expandedItem.id === itemId
      );
      if (expandedItem) {
        this.expandedate = this.expandedate.filter(
          (expandedItem) => expandedItem.id !== itemId
        );
      } else {
        this.expandedate = [item];
      }
    },
    toggleExpand(item) {
      const itemId = item.id; // Use the unique identifier
      const expandedItem = this.expanded.find(
        (expandedItem) => expandedItem.id === itemId
      );
      if (expandedItem) {
        this.expanded = this.expanded.filter(
          (expandedItem) => expandedItem.id !== itemId
        );
      } else {
        this.expanded = [item];
      }
    },
    clickGeo(geoItem) {
      const itemId = geoItem.id; // Use the unique identifier
      const selectedItem = this.importSelected.find(
        (item) => item.id === itemId
      );
      if (selectedItem) {
        this.importSelected = this.importSelected.filter(
          (item) => item.id !== itemId
        );
      } else {
        this.importSelected.push(geoItem);
      }
    },
    isSelected(geoItem) {
      return this.importSelected.some((item) => item.id === geoItem.id);
    },

    clickrow(item) {
      let self = this;
      let data = {};
      self.leads = [];
      self.Statuses = [];
      self.historyStatus = [];
      self.usersStatuses = [];
      self.history = [];
      self.activeRequests++;
      self.loading = true;
      self.item = item;
      self.item.name = self.providers.find(
        (s) => s.id == item.provider_id
      ).name;
      data.id = item.id;
      data.provider_id = item.provider_id;
      data.message = item.message;
      data.start = item.start;
      data.geo = item.geo;
      if (item.end != undefined) {
        data.end = item.end;
      }
      axios
        .post("api/getlidsImportedProvider", data)
        .then(function (response) {
          self.leads = response.data;
          self.leads.map(function (e) {
            if (e.updated_at) {
              e.date_updated = e.updated_at.substring(0, 10);
            }
            if (e.created_at) {
              e.date_created = e.created_at.substring(0, 10);
            }
            try {
              e.status =
                self.statuses.find((s) => s.id == e.status_id).name || "";
            } catch (error) {
              e.status = "";
            }

            try {
              e.provider = self.providers.find(
                (p) => p.id == e.provider_id
              ).name;
            } catch (error) {
              e.provider = "";
            }
          });
          self.filterStatuses();
          // self.loading = false;
        })
        .catch(function (error) {
          console.log(error);
        })
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });

      this.$nextTick(() => {
        const element = document.getElementById("info_prov");
        if (element) {
          element.scrollIntoView({ behavior: "smooth" });
        }
      });
    },
    getOffices() {
      let self = this;
      self.filterOffices = self.$attrs.user.office_id;
      axios
        .get("/api/getOffices")
        .then((res) => {
          self.offices = res.data;
          // if (self.$attrs.user.role_id == 1) {
          //   self.offices.unshift({ id: 0, name: "--выбор--" });
          //   self.filterOffices = self.offices[1].id;
          // }
          if (self.$attrs.user.office_id > 0) {
            self.offices = self.offices.filter(
              (o) => o.id == self.$attrs.user.office_id
            );
          }
        })
        .catch((error) => console.log(error));
    },
    getProviders() {
      let self = this;
      axios
        .get("/api/provider")
        .then((res) => {
          self.providers = res.data.map(
            ({ name, id, related_users_id, baer }) => ({
              name,
              id,
              related_users_id,
              baer,
            })
          );
          self.getImports();
        })
        .catch((error) => console.log(error));
    },
    sumField(key) {
      return this.filter_imports.reduce((a, b) => a + (b[key] || 0), 0);
    },
    getImports() {
      let self = this;
      self.activeRequests++;
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
              geo,
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
              geo,
            })
          );
          self.i_geos = _.uniq(
            _.map(self.imports, (el) => {
              return el.geo;
            })
          );
          if (self.$attrs.user.office_id > 0) {
            let office_id = self.$attrs.user.office_id;
            self.imports = self.imports.map((i) => {
              let hm_json = JSON.parse(i.hm_json);
              i.hmnew = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmnew;
              i.hmrenew = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmrenew;
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
              i.hmnoans = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmnoans;
              i.hmnointerest = hm_json.filter((f) => {
                return f.office_id == office_id;
              })[0].hmnointerest;
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

          // if (self.$attrs.user.group_id > 0) {
          //   self.imports = self.imports.filter((i) => {
          //     return (
          //       i.group_ids == null ||
          //       JSON.parse(i.group_ids).includes(self.$attrs.user.group_id)
          //     );
          //   });
          // }
          self.ImportedProvLids();
        })
        .catch((error) => console.log(error))
        .finally(() => {
          this.activeRequests--;
          if (this.activeRequests === 0) {
            this.loading = false;
          }
        });
    },
    getUsers() {
      let self = this;
      axios
        .get("/api/users")
        .then((res) => {
          self.users = res.data.map(({ name, id, role_id, fio }) => ({
            name,
            id,
            role_id,
            fio,
          }));
        })
        .catch((error) => console.log(error));
    },
    getUsers_del() {
      let self = this;

      axios
        .post("/api/getusers", self.related_user)
        .then((res) => {
          self.users = res.data.map(({ name, id, role_id, fio, hmlids }) => ({
            name,
            id,
            role_id,
            fio,
            hmlids,
          }));
        })
        .catch((error) => console.log(error));
    },
    editItem(item) {
      console.log(item);
      this.editedItem = Object.assign({}, item);
      if (item.message != undefined) {
        this.editedIndex = this.imports.indexOf(item);
        this.setBaer();
      } else {
        this.editedIndex = this.providerSummaries.findIndex((provider) =>
          provider.dates.some((date) =>
            date.geo.some((geo) => geo.id === item.id)
          )
        );

        //this.editedIndex = this.importsProvLeads.indexOf(item);
      }
      this.dialog = true;
    },
    close() {
      this.dialog = false;
      this.$nextTick(() => {
        this.editedItem = {};
        this.editedIndex = -1;
      });
      this.errorMessages = [];
    },
    save() {
      if (this.errorMessages.length) return;
      if (this.editedIndex > -1) {
        if (this.editedItem.message != undefined) {
          this.saveLoads(this.editedItem);
          Object.assign(this.imports[this.editedIndex], this.editedItem);
        } else {
          const provider = this.providerSummaries[this.editedIndex];
          const dateIndex = provider.dates.findIndex((date) =>
            date.geo.some((geo) => geo.id === this.editedItem.id)
          );
          const geoIndex = provider.dates[dateIndex].geo.findIndex(
            (geo) => geo.id === this.editedItem.id
          );
          Object.assign(
            this.providerSummaries[this.editedIndex].dates[dateIndex].geo[
              geoIndex
            ],
            this.editedItem
          );
          this.saveLoads(this.editedItem);
          // Object.assign(
          //   this.importsProvLeads[this.editedIndex],
          //   this.editedItem
          // );
        }
      }
      this.close();
    },
    saveLoads(u) {
      var form_data = new FormData();

      for (var key in u) {
        form_data.append(key, u[key]);
      }
      axios
        .post("/api/importUpdate", form_data)
        .then((res) => {})
        .catch((error) => console.log(error));
    },
    async checkName() {
      try {
        const response = await axios.post("/api/checkLoadMess", {
          load_mess: this.editedItem.message,
          id: this.editedItem.id,
        });
        this.nameExists = response.data.exists;

        if (this.nameExists) {
          this.errorMessages = ["Такое наименование уже существует!"];
        } else {
          this.errorMessages = [];
        }
      } catch (error) {
        console.error("Ошибка при проверке наименования:", error);
        this.errorMessages = [
          "Ошибка при проверке наименования. Попробуйте позже.",
        ];
      }
    },
    validateName() {
      return this.errorMessages.length ? this.errorMessages[0] : true;
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
        })
        .catch((error) => console.log(error));
    },
    onFileChange(f) {
      if (f == null) return;
      const ftype = [
        "text/comma-separated-values",
        "text/csv",
        "application/csv",
        "application/excel",
        "application/vnd.ms-excel",
        "application/vnd.msexcel",
        "text/anytext",
        "text/plain",
      ];
      if (ftype.indexOf(f.type) >= 0) {
        this.createInput(f);
      } else {
        this.files = [];
      }
    },
    txt2Array(txt) {
      let vm = this;
      vm.list_email = txt;
    },
    csvJSON(csv) {
      var vm = this;
      var lines = csv.split("\n");
      var result = [];
      var headers = lines[0].split(";");
      headers = ["name", "email", "tel", "afilyator"];
      // vm.parse_header = lines[0].split(",");
      // lines[0].split(",").forEach(function (key) {
      //   vm.sortOrders[key] = 1;
      // });

      lines.map(function (line, indexLine) {
        if (indexLine < 1) return; // Jump header line

        var obj = {};
        line = line.trim();
        var currentline = line.split(";");

        headers.map(function (header, indexHeader) {
          obj[header] = currentline[indexHeader];
        });

        result.push(obj);
      });

      result.pop(); // remove the last item because undefined values

      return result; // JavaScript object
    },
    fixdata(data) {
      var o = "",
        l = 0,
        w = 10240;
      for (; l < data.byteLength / w; ++l)
        o += String.fromCharCode.apply(
          null,
          new Uint8Array(data.slice(l * w, l * w + w))
        );
      o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w)));
      return o;
    },
    createInput(file) {
      let ext = file.name.slice(
        (Math.max(0, file.name.lastIndexOf(".")) || Infinity) + 1
      );

      let promise = new Promise((resolve, reject) => {
        var reader = new FileReader();
        var vm = this;
        reader.onload = (e) => {
          resolve((vm.fileinput = reader.result));
        };
        if (ext == "txt" || ext == "csv") {
          reader.readAsText(file);
        } else {
          reader.readAsBinaryString(file);
        }
      });

      promise.then(
        (result) => {
          let vm = this;
          if (ext == "txt" || ext == "csv") {
            vm.parse_txt_emails = vm.txt2Array(vm.fileinput);
          } else {
            var workbook = XLSX.read(result, { type: "binary" }),
              firstSheetName = workbook.SheetNames[0],
              worksheet = workbook.Sheets[firstSheetName];

            vm.list_email = XLSX.utils.sheet_to_csv(worksheet, {
              header: 1,
            });
          }
        },
        (error) => {
          /* handle an error */
          console.log(error);
        }
      );
    },
  },
  components: {
    importBTC,
    importxlsx,
    logtel,
    ConfirmDlg: () => import("./ConfirmDlg"),
    selectUsers,
  },
};
</script>

<style>
.csv .v-data-table > .v-data-table__wrapper > table > tbody > tr > td {
  height: 4rem;
}
.csv .v-data-footer {
  justify-content: end;
}
.new,
.renew,
.callback,
.deposit,
.pending,
.potential,
.nointerest,
.noans {
  font-size: 1.2rem;
}
.new,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.new {
  background: #dde4e4ff !important;
}
.renew,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.renew {
  background: #c3f3ff !important;
}
.callback,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.callback {
  background: #1d91f0 !important;
}
.deposit,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.deposit {
  background: #21cb7bff !important;
}
.pending,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.pending {
  background: #a3adb7ff !important;
}
.potential,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.potential {
  background: #7fd74e !important;
}
.nointerest,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.nointerest {
  background: #a544d2 !important;
}
.noans,
.csv .theme--light.v-data-table.v-data-table--fixed-header thead th.noans {
  background: #efa123 !important;
}
.text {
  font-size: unset;
  padding: 1rem;
  font-size: 0.75rem;
  font-weight: bold;
}
.fz17 {
  font-size: 1.2rem !important;
  font-weight: bold;
  /* width: 182px; */
}
.w150 {
  min-width: 150px;
}
.csv .v-data-table > .v-data-table__wrapper {
  padding: 0 !important;
}
#tabs .v-data-table > .v-data-table__wrapper > table > tbody > tr > td,
#tabs .v-data-table > .v-data-table__wrapper > table > thead > tr > th {
  font-size: 1.1rem !important;
  font-weight: bold;
}

.v-expansion-panel-header {
  padding: 7px 24px !important;
}
.borderactive {
  border: 4px solid #47cf0b;
}
.bordernot {
  border: 4px solid transparent;
}
.center input {
  text-align: center;
}
.label {
  font-size: 16px;
  color: rgba(0, 0, 0, 0.6);
}
.radiolabel label {
  margin-bottom: 0;
}
#inspire .v-dialog .v-text-field__details {
  display: initial;
}
.status_wrp.active {
  box-shadow: 0px 0px 9.5px 5px rgb(0, 255, 98);
}
.common-table .v-data-table-header {
  background-color: white;
}

.common-column {
  width: 100px;
  text-align: center;
}

.v-data-table__wrapper {
  overflow-x: hidden;
}

.common-table .v-data-table-header th,
.common-table td {
  width: 100px;
  max-width: 100px;
}

.common-table td {
  white-space: nowrap;
}
.csv .v-data-table > .v-data-table__wrapper > table > tbody > tr > td {
  padding: 0;
}

main
  .theme--light.v-data-table
  > .v-data-table__wrapper
  > table
  > tbody
  > tr
  > td:not(.v-data-table__mobile-row),
.theme--light.v-data-table > .v-data-table__wrapper > table > thead > tr > th {
  border-bottom: none !important;
  border: none !important;
}
.csv
  .v-data-table
  > .v-data-table__wrapper
  tbody
  tr.v-data-table__expanded__content {
  box-shadow: none;
}
.csv .v-data-table tbody tr {
  box-shadow: none;
}
.csv
  .v-data-table__wrapper
  > table
  > tbody
  > tr:not(.v-data-table__expanded__content) {
  border-top: 1px solid #9561e2;
}

.pointer {
  cursor: pointer;
}

#provTable > .v-data-table__wrapper > table > tbody,
#cpl > .v-data-table__wrapper > table > tbody {
  background: #e0e0e0;
}
#dateTable > .v-data-table__wrapper > table > tbody {
  background: #eeeeee;
}
#geoTable > .v-data-table__wrapper > table > tbody {
  background: #f5f5f5;
}
.csv
  .theme--light.v-data-table
  > .v-data-table__wrapper
  > table
  > tbody
  > tr:hover:not(.v-data-table__expanded__content):not(
    .v-data-table__empty-wrapper
  ) {
  background: #f9f9f9;
}
</style>
