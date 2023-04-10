SELECT
	r.created		date_oper		-- timestamp without time zone
	,null::json	for_dms
    ,(
        '{'
        || ' "SourceID": ' || r.id || ''
		|| ',"SourcePersID": ' || r.citizen_id || ''
        || ',CardType: '
			|| (CASE
					WHEN s.registration_type = 'реєстрація адреси місця проживання'
						THEN 10
					WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
						THEN 30
					ELSE 11		-- 'реєстрація адреси місця перебування'
				END)
--        || ',CardRegDate: ' || replace(left(r.created::text, 19), ' ', 'T') || ''
        || ',CardRegDate: ' || r.init_date::text || 'T00:00:00' || ''
        || ',AddrRegDate: ' ||
			(CASE
				WHEN s.registration_type = 'реєстрація адреси місця проживання'
					THEN r.init_date::text
				WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
					THEN (
							SELECT
								r1.init_date
							FROM r r1 
							WHERE r1.citizen_id = r.citizen_id
								and not r1.is_active
								and not r1.locked
							ORDER BY r1.created DESC
							LIMIT 1
						)::text
				ELSE r.init_date::text		-- 'реєстрація адреси місця перебування'
			 END) 
			|| 'T00:00:00' || ''
        -- CardRegOrgan, треба вибрати дані з DB rtgk-auth, згідно значення 'public'.statements.organization, DB rtgk-registry
        || ',CardRegOrgan: null'
		--
        || ',Person: {'
        || ',PersFam: ' || replace(d.last_name, '', '''') || ''
        || ',PersIm1: ' || COALESCE(replace(d.first_name, '', ''''), '') || ''
        || ',PersIm2: ' || COALESCE(replace(replace(d.middle_name, '', ''''), '\', ''''), '') || ''
		|| COALESCE((',PersUNZR: ' || tc.eddr_id || ''), '')
		|| COALESCE((',PersINN: ' || tc.ipn || ''), '')
        || ',PersSex: ' || COALESCE(upper(tc.sex), 'Н') || ''
        || ',PersBornDate: ' || tc.date_of_birth::text || 'T00:00:00' || ''
        || (
			CASE
				WHEN NOT tc.date_of_death IS Null
					THEN ',PersDeadDate: ' || tc.date_of_death::text || 'T00:00:00' || ''
				ELSE ''
			END
		)
		|| ',PersCitiz: ' || c.country_alfa3 || ''
		-- якщо людина народилася за межами України
		|| (
			CASE
			WHEN cb.country_alfa3 = 'UKR'
				-- Born_address якщо людина народилася в України, треба вибрати дані з DB address
				THEN ',Born_address: null,Born_address_txt: null'
			ELSE
				-- якщо людина народилася за межами України
				',Born_address: ['
				|| '{'
				|| 'type: country'
				|| ',name: ' || COALESCE(cb.country_short_name, '') || ''
				|| '}'
				|| ']'
				|| ',Born_address_txt: ' 
					|| replace(
						replace(
							regexp_replace(COALESCE(tc.foreign_place_of_birth, ''), '\n|\t', '', 'g')
							, '', '''')
						, '\', '/')
					|| ''
			END
		)
		--
		|| '}'		-- Person
		--
        || ',Document: ' 
		|| (
			CASE
				WHEN dt.dms_code is NULL
					THEN 'null'
			ELSE
				'{'
				|| 'DocType: ' || dt.dms_code || ''

				|| ',DocSeria: ' || COALESCE(regexp_replace(replace(regexp_replace(d.code, '\d', '','g'), '\', ''), '-|\n|\t|\r', '', 'g'), '') || ''
				|| ',DocNomer: ' || COALESCE(regexp_replace(d.code, '\D','','g'), '') || ''
				|| ',DocDate: ' || d.issue_date::text || 'T00:00:00' || ''
				|| ',DocOrgan: ' 
					|| COALESCE(replace(replace(regexp_replace(d.issued_by, '\n|\t|\r', '', 'g'), '', ''''), '\', '/'), '')
					|| ''
				|| '}'
			END)
        -- CardRegReason
        || ',CardRegReason: '
        || (CASE
				WHEN s.process_reason = 1 and s.registration_type = 'реєстрація адреси місця проживання' and s.method = 'без подання (архівні дані)'
                    THEN 5 -- Внесення відомостей про реєстрацію місця проживання до 04.04.2016
                WHEN s.process_reason = 1 and s.registration_type = 'зняття з реєстрації адреси місця проживання' and s.method = 'без подання (архівні дані)'
                    THEN 16 -- Внесення відомостей про зняття з реєстрації місця проживання до 04.04.2016
                WHEN s.process_reason = 1 and s.method = 'ЦВК'
                    THEN 20 -- Дані отримані з ДРВ ЦВК
                WHEN s.process_reason = 9 and NOT tc.citizenship[1] = '1437c9b6-370f-11e7-8ed7-000c29ff5864'
                    and (s.new_residence_country is Null or NOT s.new_residence_country = '1437c9b6-370f-11e7-8ed7-000c29ff5864')
                    THEN 12 -- Відсутність підстав для перебування іноземним громадянам на території України
				WHEN pr.dms_code = 1 and NOT s.subject_type = 'суб’єкт операції реєстраціїї / зняття з реєстрації' 
                    THEN 2 -- Заява законного представника
                ELSE pr.dms_code
            END)::text
        -- CardRegReason
        || ',RegType: null'
		-- Reg_address, треба вибрати дані з DB address, згідно значення public.register.residence_id, DB rtgk-registry
        || ',Reg_address: null'
		-- InOutType, треба вибрати дані з DB address, згідно значення public.register.residence_id, DB rtgk-registry
        || ',InOutType: null'
		-- InOut_address, треба вибрати дані з DB address
        || ',InOut_address: null'
		|| '}'
    )::json Registration
	,null::json				CardRegOrgan
	,s.organization								-- для формування CardRegOrgan
    ,r.id					r_id				-- для формування SourceID
    ,r.citizen_id								-- для формування SourcePersID, Person, Document
    -- для формування Born_address, [БД address]
	,null::json							Born_address
    ,tc.country_of_birth
    ,tc.region_of_birth
    ,tc.area_of_birth
    ,tc.place_of_birth
	,null::json							Born_address_txt
	,tc.foreign_place_of_birth
	-- для формування Reg_address, [БД address]
	,null::json							RegType
	,null::json							Reg_address
	,r.building_id						-- для RegType
    ,r.residence_id                     -- для Reg_address
	-- для формування InOut_address, [БД address]
	,null::json							InOutType
	,null::json							InOut_address
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_address
		ELSE s.current_address 
	END)	current_address
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_residence_country
		ELSE s.current_residence_country 
	END)	current_residence_country
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_locality
		ELSE s.current_locality 
	END)	current_locality					-- '538d7492-371b-11e7-b112-000c29ff5864' = 'Київ'
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_residence_district
		ELSE s.current_residence_district 
	END)	current_residence_district
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_building
		ELSE s.current_building 
	END)	current_building					-- для InOutType
	,(CASE 
		WHEN s.registration_type = 'зняття з реєстрації адреси місця проживання'
			THEN s.new_residence
		ELSE s.current_residence 
	END)	current_residence
FROM public.r r
    JOIN public.statements s ON s.id = r.statement_id and not s.locked 
    JOIN public.tbl_citizens tc ON r.citizen_id = tc.id
	--
    JOIN public.documents d ON d.citizen_id = tc.id and d.is_active
    JOIN public.document_types dt ON d.document_type_id = dt.id
    JOIN eap.country c ON c.country_guid = tc.citizenship[1] and not c.country_rec_is_locked and c.country_rec_is_actual
	--
    JOIN eap.country cb ON cb.country_guid = tc.country_of_birth and not cb.country_rec_is_locked and cb.country_rec_is_actual
	--
	JOIN public.process_reasons pr ON pr.id = s.process_reason and not pr.locked
WHERE s.state = 'виконана'
	and s.method <> ''
	--and r.created::date = CURRENT_DATE - 1
    and r.created::date = '2021-05-15'
    and not r.locked
	and r.is_active