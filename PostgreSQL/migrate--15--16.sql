ALTER TABLE account add account_company varchar(255) NULL;

CREATE TABLE IF NOT EXISTS pricing_plan (
  pricing_plan_id integer  NOT NULL,
  pricing_plan_text varchar(20) NOT NULL,
  pricing_plan_sort_order integer NOT NULL,
  PRIMARY KEY (pricing_plan_id)
) ;

INSERT INTO pricing_plan (
    pricing_plan_id,
    pricing_plan_text,
    pricing_plan_sort_order
) VALUES
    (1, 'Professional', 1),
    (2, 'Enterprise', 2),
    (3, 'Integrator', 3);

ALTER TABLE account add pricing_plan_id integer NULL;

UPDATE account SET pricing_plan_id=1;
ALTER TABLE account ALTER COLUMN pricing_plan_id SET NOT NULL;

ALTER TABLE account ADD CONSTRAINT accprice_fk FOREIGN KEY (pricing_plan_id)
    REFERENCES pricing_plan (pricing_plan_id);
